<?php
require_once(__DIR__.'/../common.php');
require_once(__DIR__.'/../matrix.php');
require_once(__DIR__.'/../visitors.php');

class Localization {

    private $matrix;
    private $espeak;

    public function __construct() {
        global $conf;
        $this->matrix = new Matrix($conf['matrix']['homeserver'], $conf['matrix']['token']);
        $this->espeak = popen("exec espeak-ng -v fi -p 60", "w");
    }

    function notice($msg, $dom = NULL) {
        global $conf;
        $this->matrix->notice($conf['matrix']['room'], $msg, $dom);
    }

    function speak($msg) {
        // FIXME Escape SSML sequences
        fwrite($this->espeak, "$msg\n");
        fflush($this->espeak);
    }

    function hacklab_is_empty_msg($a) {
        $msg = "Hacklabilta poistuttiin.";
        switch (count($a)) {
        case 0:
            $this->notice($msg);
            return;
        case 1:
            $msg .= ' Paikalla oli';
            break;
        default:
            $msg .= ' Paikalla olivat';
        }

        // Matrix HTML message
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->appendChild($dom->createTextNode($msg.":"));
        $table = $dom->createElement("ul");
        $msg .= " ";

        foreach ($a as $user => $visits) {
            $row = $dom->createElement("li");
            $row->appendChild($dom->createElement("strong",$user));

            $msg .= "$user (";
            $range = "";
            foreach($visits as $visit) {
                $range .=
                    date('H:i', $visit['enter']).
                    '–'.
                    date('H:i', $visit['leave']).
                    ', ';
            }
            // Add closing brace before comma+space the hard way
            $range = substr($range, 0, -2);

            $msg .= $range . '), ';
            $row->appendChild($dom->createTextNode(" ".$range));
            $table->appendChild($row);
        }
        $dom->appendChild($table);
        $this->notice(substr($msg, 0, -2), $dom); // Remove comma+space
    }

    public function last_leave($a) {
        // Not used anymore. Using lock notification via Hackbus.
    }

    public function first_join($a) {
        // Not used anymore. Using lock notification via Hackbus.
    }

    public function hackbus($event, $value) {
        switch ($event) {
        case 'visitor_info':
            if ($value[0]) { // If armed
                $this->hacklab_is_empty_msg(find_leavers($value[2]));
                exec('sudo systemctl stop paikalla.target');
            } else { // If unarmed
                $nick = $value[1];
                $msg = " saapui Hacklabille.";

                // Matrix HTML message
                $dom = new DOMDocument('1.0', 'UTF-8');
                $dom->appendChild($dom->createElement("strong", $nick));
                $dom->appendChild($dom->createTextNode($msg));

                $this->notice($nick.$msg, $dom);
                exec('sudo systemctl start paikalla.target');
            }
            break;
        case 'sauna':
            switch ($value) {
            case 'heating':
                $msg = "Sauna lämpenee. Tunnin päästä pääsee löylyihin! ";
                $plain = "Seuraa lämpenemistä osoitteessa https://tilastot.jkl.hacklab.fi/sauna";

                $dom = new DOMDocument('1.0', 'UTF-8');
                $dom->appendChild($dom->createTextNode($msg." 😅 "));
                $link = $dom->createElement("a", "Seuraa lämpenemistä");
                $link->setAttribute("href", "https://tilastot.jkl.hacklab.fi/sauna");
                $dom->appendChild($link);

                $this->notice($msg.$plain, $dom);
                break;
            case 'active':
                $this->notice("Saunominen alkoi juuri!");
                break;
            }
            break;
        }
    }

    public function evening_start($visits) {
        if (count($visits) === 0) {
            $this->speak('Tunnistaudu ennen kuin kerhoilta voi alkaa!');
        } else {
            $msg = "Kerhoilta alkoi, paikalla ";
            $msg .= count($visits) > 1 ? 'ovat ' : 'on ';
            foreach ($visits as $user) {
            $msg .=
                $user['nick'].' (saapui '.
                date('H:i', $user['enter']).
                '), ';
            }
            $msg = substr($msg, 0, -2); // Remove comma+space
            $msg .= ". Tervetuloa!";
            $this->notice($msg);
            $this->speak('Kerhoilta aloitettu.');
        }
    }

    // Radio controlled button (433MHz) not used any more
    public function radio_button($e) {
        return false;
    }

    public function ssh_stream_start() {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $plain = 'Hacklab-telkkarissa alkoi lähetys. Katso selaimella '.
               'osoitteessa https://tv.jkl.hacklab.fi/ tai avaa '.
               'videosoittimessasi https://tv.jkl.hacklab.fi/hls/stream.m3u8';
        $dom->appendChild($dom->createTextNode('Hacklab-telkkarissa alkoi lähetys. '));
        $link = $dom->createElement("a");
        $link->appendChild($dom->createTextNode('Katso selaimella'));
        $link->setAttribute('href', 'https://tv.jkl.hacklab.fi/');
        $dom->appendChild($link);
        $dom->appendChild($dom->createTextNode(' tai avaa videosoittimessasi '));
        $code = $dom->createElement("code");
        $code->appendChild($dom->createTextNode('https://tv.jkl.hacklab.fi/hls/stream.m3u8'));
        $dom->appendChild($code);
        $this->notice($plain, $dom);
        print("\n");
    }
}
