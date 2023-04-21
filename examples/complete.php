<?php
namespace ITRocks\Class_Use;

use ITRocks\Class_Use\Repository\Type;

include __DIR__ . '/../bin/autoload.php';

//=================================================================================================
// Calculate cache

$repository = new Repository(Repository::VENDOR, __DIR__ . '/..');
$repository->scanDirectory();
$repository->classify();
$repository->save();

//=================================================================================================
// List all references into Repository

echo "\nThese are all references into the " . Repository::class . " class :\n";
$found = $repository->search([Type::CLASS_ => Repository::class]);
foreach ($found as $key => $use) {
	[, $use, $type, $file, $line] = $use;
	if (!$key) {
		echo "    the class is into file $file\n";
	}
	echo "#$key. line $line refers to $type $use\n";
}

//=================================================================================================
// Search all uses of the class Repository

echo "\nThese are all use references to the " . Repository::class . " class :\n";
$found = $repository->search([Type::USE => Repository::class]);
foreach ($found as $key => $use) {
	[$class,, $type, $file, $line] = $use;
	echo "#$key. $type into $file";
	if ($class) echo " class $class";
	echo " line $line";
	echo "\n";
}

// Only after new keyword

$class = Repository::class;
$type  = 'new';
echo "\nThese are all the $type references to the $class class :\n";
$found = $repository->search([Type::USE => Repository::class, Type::TYPE => $type]);
foreach ($found as $key => $use) {
	[$class,,, $file, $line] = $use;
	echo "#$key. into $file";
	if ($class) echo " class $class";
	echo " line $line";
	echo "\n";
}

// Only static calls into a given file

$class = Repository::class;
$type  = 'static';
$file  = 'src/Console.php';
echo "\nThese are all the $type references to the $class class into the file $file\n";
$found = $repository->search([
	Type::USE => Repository::class,
	Type::FILE       => $file,
	Type::TYPE       => $type
]);
foreach ($found as $key => $use) {
	[$class,,,, $line] = $use;
	echo "#$key. in";
	if ($class) echo " class $class";
	echo " line $line";
	echo "\n";
}

echo "\n";

//=================================================================================================
// Purge cache. Remove this if you want to study how class use data it stored.

echo "Purge cache directory " . $repository->getHome() . "/cache\n";
$repository->purgeCache();
rmdir($repository->getHome() . '/cache');
