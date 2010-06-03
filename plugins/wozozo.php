<?php
return new OweleoPlugin(null, array(
    'on_load' => function(Oweleo $oweleo) {
        while (true) {
            $oweleo->notice('#nequal@freenode', 'ゆどうふ');
            sleep(mt_rand(300, 3600));
        }
    },
));

