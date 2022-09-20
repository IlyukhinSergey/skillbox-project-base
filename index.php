<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function my_autoloader($class)
{
    if (file_exists('controller/' . $class . '.php')) {
        require_once 'controller/' . $class . '.php';
    }
}
spl_autoload_register('my_autoloader');

$urlList = ['/user/' => [], '/user/search/' => [], '/admin/user/' => [], '/file/' => [], '/directory/' => [],
    '/files/share/' => []];

$method = $_SERVER['REQUEST_METHOD'];
$request = $_REQUEST;
$arrayUri = explode('?', $_SERVER['REQUEST_URI']);
$url = $arrayUri[0];

if (!empty($arrayUri[1])) {
    parse_str($arrayUri[1], $param);
} else {
    $param = '';
}

if (array_key_exists($url, $urlList)) {
    callfunction($url, $method, $param);
}

function callfunction($url, $method, $param)
{
    if ($url == '/user/') {
        $controller = new User();
        $controller->user($method, $param);
    }
}
