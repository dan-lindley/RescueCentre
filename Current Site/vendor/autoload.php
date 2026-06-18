<?php
require(__DIR__.'/SplClassLoader.php');

$loader = new Example\Psr4AutoloaderClass;
$loader->register();

$loader->addNamespace('FireText', __DIR__.'/FireText-PHP-SDK/src/FireText');
$loader->addNamespace('Laminas\Hydrator', __DIR__.'/Laminas/Hydrator/src');
$loader->addNamespace('Laminas\StdLib', __DIR__.'/Laminas/StdLib/src');
$loader->addNamespace('Webmozart\Assert', __DIR__.'/Webmozart/Assert/src');