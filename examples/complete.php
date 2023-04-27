<?php
namespace ITRocks\Class_Use;

use ITRocks\Class_Use\Token\Name;

include __DIR__ . '/../bin/autoload.php';

//=================================================================================================
// Calculate cache

$index = new Index(Index::VENDOR, __DIR__ . '/..');
$index->scanDirectory();
$index->classify();
$index->save();

//=================================================================================================
// List all class uses into Index

echo "\nThese are all class uses into the " . Index::class . " class:\n";
$found = $index->search([T_CLASS => Index::class]);
foreach ($found as $key => $use) {
	[, $type, $use, $file, $line, $token_key] = $use;
	$type = Name::OF[$type];
	if (!$key) {
		echo "    the class is into file $file\n";
	}
	echo "#$key. token $token_key line $line refers to $type $use\n";
}

//=================================================================================================
// Search all uses of the class Index

echo "\nThese are all use of the class " . Index::class . ":\n";
$found = $index->search([T_USE => Index::class]);
foreach ($found as $key => $use) {
	[$class, $type,, $file, $line, $token_key] = $use;
	$type = Name::OF[$type];
	echo "#$key. $type into $file";
	if ($class) echo " class $class";
	echo " token $token_key line $line";
	echo "\n";
}

// Only after new keyword

$class = Index::class;
echo "\nThese are all the new class $class uses:\n";
$found = $index->search([T_TYPE => T_NEW, T_USE => Index::class]);
foreach ($found as $key => $use) {
	[$class,,, $file, $line, $token_key] = $use;
	echo "#$key. into $file";
	if ($class) echo " class $class";
	echo " token $token_key line $line";
	echo "\n";
}

// Only static calls into a given file

$class = Index::class;
$file  = 'src/Console.php';
echo "\nThese are all the static class $class uses into the file $file\n";
$found = $index->search([T_TYPE => T_STATIC, T_USE => Index::class, T_FILE => $file]);
foreach ($found as $key => $use) {
	[$class,,,, $line, $token_key] = $use;
	echo "#$key. in";
	if ($class) echo " class $class";
	echo " token $token_key line $line";
	echo "\n";
}

echo "\n";

//=================================================================================================
// Purge cache. Remove this if you want to study how class use data it stored.

echo "Purge cache directory " . $index->getHome() . "/cache\n";
$index->purgeCache();
rmdir($index->getHome() . '/cache');
