<?php
require_once 'HTTP/Request2.php';

return (object) array(
    'pattern' => '/(s?https?:\/\/[-_\.!~\*\'\(\)a-zA-Z0-9;\/?:@&=\+$,%#]+)/',
    'on_privmsg' => function($m, array &$stacks = array(), $match) {
        list($prefix, $mes) = $m->params;
        $request = new HTTP_Request2($match, HTTP_Request2::METHOD_GET);
        try {
            $response = $request->send();
            if (200 == $response->getStatus()) {
                if (strpos($response->getHeader('Content-Type'), 'html') !== false) {
                    $title = preg_match('/<title>(.+?)<\/title>/i', $response->getBody(), $t)? mb_convert_encoding($t[1], 'utf-8', 'auto'): 'No Title';
                    if (preg_match('/&#[xX][0-9a-zA-Z]{2,8};/', $title)) {
                        $title = preg_replace("/&amp;#[xX]([0-9a-zA-Z]{2,8});/e", "'&amp;#'.hexdec('$1').';'", $title);
                        $title = mb_decode_numericentity($title, array(0x0, 0x10000, 0, 0xfffff), "utf-8");
                    }
                    $title .= sprintf(' [%s]', $response->getHeader('Content-Type'));
                } else {
                    $title = sprintf('[%s] %dKB', $response->getHeader('Content-Type'), $response->getHeader('Content-Length') / 1024);
                }

                $stacks[] = array(
                    'prefix' => $prefix,
                    'message' => $title,
                );
            }
        } catch (Exception $e) {}
    }
);

