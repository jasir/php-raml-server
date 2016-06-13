<?php

define('APPLICATION_PATH', __DIR__ . "/..");

require APPLICATION_PATH . '/vendor/autoload.php';

\Tracy\Debugger::enable();

//RobotLoader

$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(APPLICATION_PATH . '/src');
$loader->addDirectory(APPLICATION_PATH . '/controllers');
$loader->setCacheStorage(new Nette\Caching\Storages\FileStorage(__DIR__ . '/temp'));
$loader->register();