<?php

use GlpiPlugin\Centreon\Host;

include ('../../../inc/includes.php');

Html::header(Host::getTypeName(),
            $_SERVER['PHP_SELF'],
            "plugins",
            Host::class,
            "Host");
\Search::show(Host::class);
\Html::footer();
