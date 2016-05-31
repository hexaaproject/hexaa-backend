<?php

use Symfony\Component\HttpFoundation\Request;

//Use bootstrap file to speed up the bootstrapping process
//$loader = require_once __DIR__.'/../app/bootstrap.php.cache';

$loader = require_once __DIR__.'/../app/autoload.php';

require_once __DIR__.'/../app/AppKernel.php';
require_once __DIR__.'/../app/AppCache.php';

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
$kernel = new AppCache($kernel);
Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
