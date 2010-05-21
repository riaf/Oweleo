<?php
return (object) array(
    'pattern' => '/^(.+)$/',
    'on_privmsg' => function($m, array &$stacks = array(), $match) {
        echo $match, PHP_EOL;
        list($prefix, $mes) = $m->params;
        if ($match == 'hello') {
            $stacks[] = array(
                'prefix' => $prefix,
                'message' => 'hello!',
            );
        }
    }
);

