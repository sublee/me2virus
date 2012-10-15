<?php
require_once dirname(__FILE__).'/../../../lib/me2pheungp-prod/Me2.php';
require_once dirname(__FILE__).'/appkey.php';
require_once dirname(__FILE__).'/db.php';

$posturl = $_GET['post'];

$route = array();
foreach (db::route($posturl) as $post) {
    $route[] = array('name' => $post->name, 'url' => $post->url);
}

header('Content-type: application/json');
echo json_encode($route);
