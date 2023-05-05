<?php
namespace ITRocks\Class_Use;

use Exception;

include __DIR__ . '/autoload.php';
include __DIR__ . '/error_handler.php';

$console = new Console;
echo $console->quickDocumentation() . "\n";
if (!is_array($_SERVER['argv'] ?? null)) {
	echo "Error: please execute from command line only\n";
	return;
}
foreach ($_SERVER['argv'] as $argv) {
	if (!is_string($argv)) {
		echo "Error: accepts string command line arguments only\n";
		return;
	}
}
/** @var array<int,string> $arguments phpstan bleedingEdge */
$arguments = $_SERVER['argv'];
try {
	(new Console())->run(array_slice($arguments, 1));
}
catch (Exception $exception) {
	echo 'Error: ' . $exception->getMessage();
}
