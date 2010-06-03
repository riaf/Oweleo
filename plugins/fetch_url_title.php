<?php
require_once 'HTTP/Request2.php';

return new OweleoPlugin('/(s?https?:\/\/[-_\.!~\*\'\(\)a-zA-Z0-9;\/?:@&=\+$,%#]+)/', array(
    'on_privmsg' => function(Oweleo $oweleo, Net_IRC_Message $msg, $match) {
        list($prefix, $mes) = $msg->params();
        $request = new HTTP_Request2($match[1], HTTP_Request2::METHOD_GET);
        $request->setConfig(array('follow_redirects' => true));
        try {
            $response = $request->send();
            if (200 == $response->getStatus()) {
                if (strpos($response->getHeader('Content-Type'), 'html') !== false) {
                    $body = mb_convert_encoding($response->getBody(), 'utf-8', 'ASCII,JIS,EUC-JP,UTF-8,SJIS');
                    $title = preg_match('/<title>(.+?)<\/title>/i', $body, $t)? $t[1]: 'No Title';
                    if (preg_match('/&#[xX][0-9a-zA-Z]{2,8};/', $title)) {
                        $title = preg_replace("/&amp;#[xX]([0-9a-zA-Z]{2,8});/e", "'&amp;#'.hexdec('$1').';'", $title);
                        $title = mb_decode_numericentity($title, array(0x0, 0x10000, 0, 0xfffff), "utf-8");
                    }
                    $title = mb_strimwidth($title, 0, 140, '...', 'utf-8');
                    $title .= sprintf(' [%s]', $response->getHeader('Content-Type'));
                } else {
                    $title = sprintf('[%s] %dKB', $response->getHeader('Content-Type'), $response->getHeader('Content-Length') / 1024);
                }
                $oweleo->notice($prefix, $title);
            }
        } catch (Exception $e) {}
    }
));

