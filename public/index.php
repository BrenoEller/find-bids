<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Core/Router.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');


use App\Core\Router;

$router = new Router();
require __DIR__ . '/../config/routes.php';

$response = $router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

header('Content-Type: application/json; charset=UTF-8');
echo $response;