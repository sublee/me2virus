<?php
require_once dirname(__FILE__).'/../lib/me2pheungp/Me2.php';
require_once dirname(__FILE__).'/appkey.php';
require_once dirname(__FILE__).'/db.php';

$posturl = $_GET['post'];

if (!($post = db::post($posturl))) {
    die('false');
}

$name = $post->name;
$url = $post->url;

$route = array();
foreach (db::route($posturl) as $ancestor) {
    $route[] = array('name' => $ancestor->name, 'url' => $ancestor->url);
}

$scale = db::scale($posturl);
$infectees = db::infectees($posturl);

header('Content-type: application/json');
echo json_encode(compact('name', 'url', 'route', 'scale', 'infectees'));

