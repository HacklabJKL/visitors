<?php
// Class for sending messages to Matrix chat
class Matrix {
    private $ch;

    public function __construct($hs, $token) {
        $this->hs = $hs;
        $this->token = $token;

        // Configure Matrix cURL handle
        $this->ch = curl_init();
        curl_setopt_array($this->ch, [
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_VERBOSE => TRUE,
            CURLOPT_FAILONERROR => TRUE,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer '.$this->token,
            ],
        ]);
    }

    function notice($room, $msg, $dom = NULL) {
        return $this->msg('m.notice', $room, $msg, $dom);
    }

    function msg($msgtype, $room, $msg, $dom = NULL) {
        $payload = [
            'body'    => $msg,
            'msgtype' => $msgtype,
        ];

        if ($dom !== NULL) {
            // We clean the XML up for broken Matrix appservices such
            // as mx-puppet-discord by removing things which confuse them
            $xml = $dom->saveXML();
            // Collapse successive whitespaces (including newlines) into one
            $xml = preg_replace('/\s+/', ' ', $xml);
            // Clean the start and end; Remove XML prolog and trailing whitespaces
            $xml = preg_replace('/^<\?xml[^>]*>\s?|\s$/', '',  $xml);

            $payload += [
                'format' => 'org.matrix.custom.html',
                'formatted_body' => $xml,
            ];
        }

        return $this->event($payload, $room);
    }

    function event($payload, $room, $event_type = 'm.room.message') {
        $url = $this->hs . '/_matrix/client/r0/rooms/' . urlencode($room) . '/send/' . $event_type . '/' . uniqid();

        curl_setopt_array($this->ch, [
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        return curl_exec($this->ch);
    }
}
