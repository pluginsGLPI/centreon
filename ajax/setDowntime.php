<?php

use GlpiPlugin\Centreon\Host;

include ('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");

if(isset($_POST)) {
    parse_str($_POST['params'], $params);
    $hostid = $_POST['hostid'];
    $host = new Host();
    $resdowntime = $host->setDowntime($hostid, $params);
}