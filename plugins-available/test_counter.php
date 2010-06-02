<?php
return (object) array(
    'pattern' => '/^counter start$/',
    'on_privmsg' => function($m, array &$stacks = array(), $match) {
        $i = 0;
        for($i=0; $i<10; $i++) {
            $stacks[] = array(
                'prefix' => '#nequal@nequal',
                'message' => 'counter: '. $i,
            );
            $m->oweleo->send_stacks();
            sleep(1);
        }
    }
);

