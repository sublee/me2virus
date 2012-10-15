<?php
require_once dirname(__FILE__).'/db.php';

$name = trim($_GET['user']);
$method = trim($_GET['method']);

if (in_array($method, array('reject', 'accept', 'rejected', 'accepted'))) {
    echo json_encode(db::$method($name));
}

