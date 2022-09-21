<?php
require_once(__DIR__.'/../common.php');
require_once(__DIR__.'/../matrix.php');
require_once(__DIR__.'/../visitors.php');

class Localization {

    private $matrix;

    public function __construct() {
        global $conf;
        $this->matrix = new Matrix($conf['matrix']['homeserver'], $conf['matrix']['token']);
    }

    function notice($msg, $dom = NULL) {
        global $conf;
        $this->matrix->notice($conf['matrix']['room'], $msg, $dom);
    }

    // FIXME should be in the superclass
    function matrix_native($payload) {
        $this->matrix->msg_raw($payload, $conf['matrix']['room']);
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
        $msg = "Hacklab on nyt tyhjä. Paikalla oli";
        $msg .= count($a) > 1 ? 'vat' : '';

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

    public function first_join($a) {
        $msg = "Ensimmäisenä saapui ";

        // Matrix HTML message
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->appendChild($dom->createTextNode($msg));
        $dom->appendChild($dom->createElement("strong",$a['nick']));

        $this->notice($msg.$a['nick'], $dom);
    }

    public function hackbus($event, $value) {
        // empty
    }
}
