<?php
namespace ITRocks\Depend;

echo "Scan your PHP project for dependencies\n";
echo "usage : ./run [reset] [vendor] [pretty]\n";
echo "- reset  : purge dependency cache and calculate it from scratch\n";
echo "- vendor : include dependencies from the vendor source code directory\n";
echo "- pretty : updated cache files use json pretty print\n\n";

if (($_SERVER['argv'][0] ?? '') !== ($_SERVER['PHP_SELF'] ?? '-')) {
	die();
}

spl_autoload_register(function(string $class_name) : void {
	include __DIR__ . '/src/' . str_replace('\\', '/', substr($class_name, 15)) . '.php';
});

$flags = 0;
if (in_array('reset', $_SERVER['argv'], true)) {
	$flags |= Repository::RESET;
}
if (in_array('vendor', $_SERVER['argv'], true)) {
	$flags |= Repository::VENDOR;
}
if (in_array('pretty', $_SERVER['argv'], true)) {
	$flags |= Repository::PRETTY;
	$pretty = ' pretty';
}
else {
	$pretty = '';
}

$repository = new Repository($flags);
echo ($flags & Repository::RESET) ? 'reset' : 'update';
if ($flags & Repository::VENDOR) echo ' with vendor';
echo ' from directory ' . $repository->getHome();
echo "\n";

echo date('Y-m-d H:i:s') . "\n";
$total = microtime(true);

$start = microtime(true);
$repository->scanDirectory();
echo "- scanned $repository->directories_count directories and $repository->files_count files in "
	. round(microtime(true) - $start, 3) . " seconds\n";

$start = microtime(true);
$repository->classify();
echo "- classified $repository->references_count references in "
	. round(microtime(true) - $start, 3) . " seconds\n";

$start = microtime(true);
$repository->save();
echo "- saved $repository->files_count files in "
	. round(microtime(true) - $start, 3) . " seconds\n";

echo date('Y-m-d H:i:s') . "\n";
echo 'duration = ' . round(microtime(true) - $total, 3) . " seconds\n";
echo 'memory   = ' . ceil(memory_get_peak_usage(true) / 1024 / 1024) . " Mo\n";
echo "cache$pretty output into " . $repository->getTarget() . "\n";
