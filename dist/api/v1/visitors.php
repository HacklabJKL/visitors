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
default:
    http_response_code(400);
    header("Content-Type: text/plain; charset=utf-8");
    print("Invalid format\n");
}
