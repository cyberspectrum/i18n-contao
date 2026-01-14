<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Thanks to Tomas Votruba.
// https://tomasvotruba.com/blog/2019/03/28/how-to-mock-final-classes-in-phpunit
use DG\BypassFinals;

$projectRoot = dirname(__DIR__);

BypassFinals::allowPaths([
    $projectRoot . '/src/*',
]);
BypassFinals::enable();
