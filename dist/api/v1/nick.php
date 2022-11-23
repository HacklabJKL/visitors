<?php
require_once(__DIR__.'/../../../lib/common.php');

// Here are the individual SQL queries for each phase for clarity and
// below it is a mash-up of them for effectiveness
//
// SELECT mac FROM visit WHERE ip=:ip ORDER BY leave DESC LIMIT 1;
// SELECT id,changed FROM user_mac WHERE mac=:mac AND changed<:leave ORDER BY changed DESC LIMIT 1;
// SELECT nick, flappiness, stealth FROM user WHERE id=:id

// Get person info by querying by latest IP
$get_user = $db->prepare("
	SELECT v.mac, hostname, nick, changed, flappiness, stealth
	FROM visit v
	LEFT JOIN user_mac m ON (SELECT rowid
	                         FROM user_mac
	                         WHERE mac=v.mac AND changed<v.leave
	                         ORDER BY changed DESC
	                         LIMIT 1
	                        )=m.rowid
	LEFT JOIN user u ON m.id=u.id
	WHERE ip=?
	ORDER BY leave DESC
	LIMIT 1
");

// Insert device by UID. This can be used for deleting as well when :id is NULL
$insert_by_uid = $db->prepare("
	INSERT INTO user_mac (id, mac, changed)
	SELECT :id, mac, :now
	FROM visit
	WHERE ip=:ip
	ORDER BY leave DESC
	LIMIT 1
");
$insert_by_uid->bindValue('now', time()-$merge_window_sec);

// Find user ID by nick
$find_uid = $db->prepare("
	SELECT id FROM user WHERE nick=?
");

// Insert user ID if possible
$insert_user = $db->prepare("
	INSERT INTO user (nick)
	VALUES (?)
");

// Update flappiness
$update_flappiness = $db->prepare('UPDATE user SET flappiness=:val WHERE id=(SELECT id FROM user_mac WHERE mac=(SELECT mac FROM visit WHERE ip=:ip ORDER BY enter DESC LIMIT 1) ORDER BY changed DESC LIMIT 1)');
// Update stealth mode
$update_stealth = $db->prepare('UPDATE user SET stealth=:val WHERE id=(SELECT id FROM user_mac WHERE mac=(SELECT mac FROM visit WHERE ip=:ip ORDER BY enter DESC LIMIT 1) ORDER BY changed DESC LIMIT 1)');

$ip = $_SERVER['REMOTE_ADDR'];
$outerror = [
    "error" => "You are outside the lab network ($ip)",
    "errorcode" => "OUT"
];
$nick_unset = [
    "error" => "Set nickname first before changing other parameters",
    "errorcode" => "EMPTY"
];
$stealth_val = [
    "error" => "Stealth value must be 0 or 1",
    "errorcode" => "STEALTH"
];

switch ($_SERVER['REQUEST_METHOD']) {
case 'GET':
    // Allow IP queries for everybody
    if (array_key_exists('ip', $_GET)) {
        $o = db_execute($get_user, [$_GET['ip']])->fetchArray(SQLITE3_ASSOC) ?: $outerror;
        unset($o['mac']);
        unset($o['changed']);
        unset($o['flappiness']);
        unset($o['stealth']);
    } else {
        $o = db_execute($get_user, [$ip])->fetchArray(SQLITE3_ASSOC) ?: $outerror;
    }
    break;
case 'DELETE':
    db_execute($insert_by_uid, [
        'id'  => NULL,
        'ip'  => $ip
    ]);
    $o = $db->changes() === 1 ? ["success" => TRUE] : $outerror;
    break;
case 'PUT':
    $db->exec('BEGIN');

    if (array_key_exists('nick', $_GET) && $_GET['nick'] !== '') {
        // Set nick if it's in URL
        $row = db_execute($find_uid, [$_GET['nick']])->fetchArray(SQLITE3_ASSOC);
        if ($row === FALSE) {
            db_execute($insert_user, [$_GET['nick']]);
            $uid = $db->lastInsertRowid();
        } else {
            $uid = $row['id'];
        }

        // We know uid by now, let's insert
        db_execute($insert_by_uid, [
            'id'  => $uid,
            'ip'  => $ip
        ]);

        if ($db->changes() !== 1) {
            // Do not allow trolling outside the lab network
            $o = $outerror;
            break;
        }
    }
    if (array_key_exists('flappiness', $_GET) && $_GET['flappiness'] !== '') {
        // Try to set the parameter
        db_execute($update_flappiness, [
            'ip'  => $ip,
            'val' => intval($_GET['flappiness'])
        ]);

        if ($db->changes() !== 1) {
            // IP doesn't yet belong to anyone
            $o = $nick_unset;
            break;
        }
    }
    if (array_key_exists('stealth', $_GET) && $_GET['stealth'] !== '') {
        // Try to set the parameter
        switch ($_GET['stealth']) {
        case '0':
            $stealth = 0;
            break;
        case '1':
            $stealth = 1;
            break;
        default:
            $o = $stealth_val;
            break 2;
        }

        db_execute($update_stealth, [
            'ip'  => $ip,
            'val' => $stealth
        ]);

        if ($db->changes() !== 1) {
            // IP doesn't yet belong to anyone
            $o = $nick_unset;
            break;
        }
    }

    // Everything is fine!
    $o = ["success" => TRUE];
    $db->exec('END');
    break;
default:
    $o = ["error" => "Unsupported method"];
}
    
header('Content-Type: application/json; charset=utf-8');
print(json_encode($o)."\n");
