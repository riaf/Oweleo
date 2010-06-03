<?php
require_once 'HTTP/Request2.php';

return new OweleoPlugin('/^g\? (.+)$/', array(
    'on_privmsg' => function(Oweleo $oweleo, Net_IRC_Message $msg, $match) {
        list($prefix, $mes) = $msg->params();
        $request = new HTTP_Request2('http://ajax.googleapis.com/ajax/services/search/web');
        $request->setConfig(array('follow_redirects' => true));
        $request->setHeader(array(
            'Accept-Language' => 'ja',
        ));
        $url = $request->getUrl();
        $url->setQueryVariable('v', '1.0');
        $url->setQueryVariable('q', $match[1]);

        try {
            $response = $request->send();
            if (200 == $response->getStatus()) {
                $results = json_decode($response->getBody());
                $i = 0;
                foreach ($results->responseData->results as $result) {
                    if (++$i > 3) break;
                    $oweleo->notice($prefix, sprintf('%s | %s', $result->titleNoFormatting, $result->url));
                }
            }
        } catch (Exception $e) {}
    }
));

