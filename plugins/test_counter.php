<?php
return new OweleoPlugin('/^counter start$/', array(
    'on_privmsg' => function(Oweleo $oweleo, Net_IRC_Message $msg, $match) {
        list($prefix) = $msg->params();
        $i = 0;
        for($i=0; $i<10; $i++) {
            $this->notice($prefix, 'counter: '. $i);
            sleep(1);
        }
    }
));

