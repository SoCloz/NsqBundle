<?php

$vendorDir = realpath(__DIR__ . '/..') . '/vendor';
if (!is_dir($vendorDir)) {
    fputs(STDERR,
        basename($vendorDir) . " directory does not exist.\n"
            . "You must run the following commands:\n"
            . "curl -s http://getcomposer.org/installer | php\n"
            . "php composer.phar install --dev\n"
    );
    exit(1);
}

require_once $vendorDir . '/autoload.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespace('Socloz\NsqBundle', realpath(__DIR__ . '/../..'));
$loader->register();
