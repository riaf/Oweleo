<?php
return new OweleoPlugin('/^hello$/', array(
    'on_privmsg' => function(Oweleo $oweleo, Net_IRC_Message $msg) {
        list($prefix, $mes) = $msg->params();
        $oweleo->notice($prefix, 'Hello, '. $msg->nick());
    },
));

