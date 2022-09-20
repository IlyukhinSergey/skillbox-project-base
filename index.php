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
    } elseif ($url == '/admin/user/') {
        $controller = new Admin();
        if (!empty($_COOKIE['token'])) {
            $controller->checkAccess($_COOKIE['token'], $method, $param);
        } else {
            $controller->htmlNoAdmin();
        }
    } elseif ($url == '/file/') {
        $controller = new File();
        if (!empty($_COOKIE['token'])) {
            $controller->files($_COOKIE['token'], $method, $param);
        } else {
            $controller->htmlNoFile();
        }
    } elseif ($url == '/directory/') {
        if (!empty($_COOKIE['token'])) {
            $controller = new File();
            $controller->directory($_COOKIE['token'], $method, $param);
        } else {
            echo 'Пользователь не зарегистрирован';
        }
    } elseif ($url == '/files/share/'){
        $controller = new File();
        $controller->filesShare($method, $param);
    }
}
