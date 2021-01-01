<?php

include_once('classes/Database.php');
include_once('classes/API.php');

if (isset($_GET['action'])) {
    $database = new Database('localhost', 'root', '', 'obd');
    $api = new API($database->buildConnection());
    $api->process($_GET['action']);
}
