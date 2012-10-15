<?php
require_once dirname(__FILE__).'/../../../lib/me2pheungp/Me2.php';
require_once dirname(__FILE__).'/appkey.php';
require_once dirname(__FILE__).'/db.php';

header('Content-Type: text/plain');

$routes = array();
foreach (db::query("
    Select * From posts;
")->fetchAll() as $post) {
    $url = 'http://me2day.net/' . $post['name']
         . date('/Y/m/d#H:i:s', strtotime($post['posted']));
    $routes[$url] = count(db::route($url));
}

asort($routes);
