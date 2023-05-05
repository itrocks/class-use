<?php
namespace ITRocks\Class_Use;

use Exception;

include __DIR__ . '/autoload.php';
include __DIR__ . '/error_handler.php';

$console = new Console;
echo $console->quickDocumentation() . "\n";
try {
	(new Console())->run(array_slice($_SERVER['argv'], 1));
}
catch (Exception $exception) {
	echo 'Error: ' . $exception->getMessage();
}
