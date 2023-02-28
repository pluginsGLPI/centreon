<?php

use GlpiPlugin\Centreon\Host;

include ('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");

if(isset($_POST)) {
    $params = $_POST['params'];
    $hostid = (int) $_POST['hostid'];
    $host = new Host();
    $resdowntime = $host->setDowntime($hostid, $params);
}