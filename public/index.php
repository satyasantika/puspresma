<?php

use App\Support\AwalanSubpath;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Proxy yang meneruskan awalan subpath apa adanya (tidak memotong): buang awalan dari APP_URL sebelum routing.
$_SERVER = AwalanSubpath::bersihkan($_SERVER, AwalanSubpath::dariUrl(getenv('APP_URL') ?: null));

$app->handleRequest(Request::capture());
