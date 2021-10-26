<?php
require_once(__DIR__.'/../../../lib/common.php');
require_once(__DIR__.'/../../../lib/visitors.php');

// This function is from http://stackoverflow.com/a/7153133/514723 by velcrow
function utf8($num)
{
    if($num<=0x7F)       return chr($num);
    if($num<=0x7FF)      return chr(($num>>6)+192).chr(($num&63)+128);
    if($num<=0xFFFF)     return chr(($num>>12)+224).chr((($num>>6)&63)+128).chr(($num&63)+128);
    if($num<=0x1FFFFF)   return chr(($num>>18)+240).chr((($num>>12)&63)+128).chr((($num>>6)&63)+128).chr(($num&63)+128);
    return '';
}

function hackbus_read($vars)
{
    $hackbus_options = parse_ini_file(__DIR__.'/../../../hackbus.conf', TRUE);
    if ($hackbus_options === FALSE) {
        print("Configuration file invalid\n");
        exit(1);
    }

    $req = [
        "method" => "r",
        "params" => $vars,
    ];

    $bus = stream_socket_client($hackbus_options['hackbus']);
    fwrite($bus, json_encode($req)."\n");
    $out = json_decode(fgets($bus), TRUE);
    fclose($bus);
    return $out;
}

// Search with timestamp or current visitors
$req = array_key_exists('at', $_GET) ?
    [
        'lease' => 0,
        'now' => intval($_GET['at'])
    ] : [
        'lease' => $merge_window_sec,
        'now' => gettimeofday(true)
    ];
$visits = get_visitors($req);

// Allow CORS
header('Access-Control-Allow-Origin: *');

switch (@$_GET['format'] ?: @$argv[1] ?: 'text') {
case 'text':
    header("Content-Type: text/plain; charset=utf-8");
    if (empty($visits)) {
        print("Hacklab on nyt tyhjä.\n");
    } else {
        foreach ($visits as $data) {
            print($data['nick']." (saapui ".date('H:i', $data['enter']).")\n");
        }
    }
    break;
case 'json':
    header("Content-Type: application/json; charset=utf-8");
    print(json_encode($visits)."\n");
    break;
case 'json-v2':
    // Get door information
    $lab_info = hackbus_read(["in_charge", "arming_state", "open"])['result'];
    // Return in_charge only if the lab is in a state there are people
    // inside.
    $lab_info['empty'] = $lab_info['arming_state'] !== 'Unarmed';
    unset($lab_info['arming_state']);
    if ($lab_info['empty'])  {
        unset($lab_info['in_charge']);
    }
    // Nest the normal visitor info to the answer
    $lab_info['present'] = $visits;
    header("Content-Type: application/json; charset=utf-8");
    print(json_encode($lab_info)."\n");
    break;
default:
    http_response_code(400);
    header("Content-Type: text/plain; charset=utf-8");
    print("Invalid format\n");
}
