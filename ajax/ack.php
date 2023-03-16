<?php

use GlpiPlugin\Centreon\Host;

include('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");

if (isset($_POST['hostid'])) {
    $host   = new Host();
    $res_a  = $host->acknowledgement($_POST['hostid'], $_POST['params']);
}
