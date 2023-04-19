<?php
namespace ITRocks\Depend;

include __DIR__ . '/autoload.php';
include __DIR__ . '/error_handler.php';

$console = new Console;
echo $console->quickDocumentation() . "\n";
(new Console)->run(array_slice($_SERVER['argv'], 1));
