<?php
require_once dirname(__FILE__).'/db.php';

header('Content-type: application/json');
echo db::json($_GET['post']);

