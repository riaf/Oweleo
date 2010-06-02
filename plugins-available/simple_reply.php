<?php
return new OweleoPlugin(null, array(
    'on_privmsg' => function(Oweleo $oweleo, Net_IRC_Message $msg, $match) {
        list($prefix, $mes) = $msg->params();
        if ($mes == 'hello') {
            $oweleo->notice($prefix, 'hello!');
        }
    },
));

