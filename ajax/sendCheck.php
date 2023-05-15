<?php

use GlpiPlugin\Centreon\Host;

include('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");

if (isset($_POST['hostid'])) {
    $host = new Host();
    $rescheck = $host->sendCheck($_POST['hostid']);
}
