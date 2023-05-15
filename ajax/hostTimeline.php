<?php

use GlpiPlugin\Centreon\Host;

include('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");

if (isset($_GET['period']) && isset($_GET['hostid'])) {
    $param_period = $_GET['period'];
    $param_hostid = $_GET['hostid'];
    $host = new Host();
    $res  = $host->hostTimeline($param_hostid, $_GET['period']);
}
