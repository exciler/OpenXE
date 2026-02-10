<?php

use OpenXE\Kernel;
use Symfony\Component\HttpFoundation\Request;

$loader = require_once __DIR__ . '/../vendor/autoload.php';

$rootDir = dirname(__DIR__);


$dotenv = new Symfony\Component\Dotenv\Dotenv();
$dotenv->bootEnv($rootDir . '/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
if ($response->isNotFound() === false) {
    $response->send();
} else {
    require 'index.php';
}
$kernel->terminate($request, $response);