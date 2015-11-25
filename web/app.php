<?php

require_once(__DIR__ . '/../vendor/symfony/symfony/src/Symfony/Component/ClassLoader/XcacheClassLoader.php');

use Symfony\Component\ClassLoader\XcacheClassLoader;
use Symfony\Component\HttpFoundation\Request;

//Use bootstrap file to speed up the bootstrapping process
//$loader = require_once __DIR__.'/../app/bootstrap.php.cache';

$loader = require_once __DIR__.'/../app/autoload.php';

// sha1(__FILE__) generates an XCache namespace prefix
$cachedLoader = new XcacheClassLoader(sha1(__FILE__), $loader);

// register the cached class loader
$cachedLoader->register(true);

$loader->unregister();

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
