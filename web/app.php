<?php

use Symfony\Component\ClassLoader\XcacheClassLoader;
use Symfony\Component\HttpFoundation\Request;

//Use bootstrap file to speed up the bootstrapping process
$loader = require_once __DIR__.'/../app/bootstrap.php.cache';
//$loader = require_once __DIR__.'/../app/autoload.php';

//Use xcache instead of opcache, because opcode cache is just not enough
$loader = new XcacheClassLoader('sf2', $loader);
$loader->register(true);

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
