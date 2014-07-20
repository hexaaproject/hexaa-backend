<?php

require __DIR__.'/../vendor/symfony/symfony/src/Symfony/Component/ClassLoader/ApcClassLoader.php';
use Symfony\Component\ClassLoader\ApcClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;



$loader = require __DIR__.'/../vendor/autoload.php';
$loader = new ApcClassLoader('apc.loader', $loader);
$loader->add('sspmod_saml_',__DIR__.'/../vendor/simplesamlphp/modules/saml/lib/');
$loader->add('SimpleSAML_',__DIR__.'/../vendor/simplesamlphp/lib/');
$loader->add('Auth_',__DIR__.'/../vendor/simplesamlphp/vendor/openid/php-openid/');
$loader->add('SAML2_',__DIR__.'/../vendor/simplesamlphp/lib/');

// intl
if (!function_exists('intl_get_error_code')) {
    require_once __DIR__.'/../vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs/functions.php';
}

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

return $loader;
