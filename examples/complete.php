<?php
namespace ITRocks\Depend;

use ITRocks\Depend\Repository\Type;

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
foreach ($found as $key => $dependency) {
	[, $dependency, $type, $file, $line] = $dependency;
	if (!$key) {
		echo "    the class is into file $file\n";
	}
	echo "#$key. line $line refers to $type $dependency\n";
}

//=================================================================================================
// Search all uses of the class Repository

echo "\nThese are all references to the " . Repository::class . " class :\n";
$found = $repository->search([Type::DEPENDENCY => Repository::class]);
foreach ($found as $key => $dependency) {
	[$class,, $type, $file, $line] = $dependency;
	echo "#$key. $type into $file";
	if ($class) echo " class $class";
	echo " line $line";
	echo "\n";
}

// Only after new keyword

$class = Repository::class;
$type  = 'new';
echo "\nThese are all the $type references to the $class class :\n";
$found = $repository->search([Type::DEPENDENCY => Repository::class, Type::TYPE => $type]);
foreach ($found as $key => $dependency) {
	[$class,,, $file, $line] = $dependency;
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
	Type::DEPENDENCY => Repository::class,
	Type::FILE       => $file,
	Type::TYPE       => $type
]);
foreach ($found as $key => $dependency) {
	[$class,,,, $line] = $dependency;
	echo "#$key. in";
	if ($class) echo " class $class";
	echo " line $line";
	echo "\n";
}

echo "\n";

//=================================================================================================
// Purge cache. Remove this if you want to study how dependency data it stored.

echo "Purge cache directory " . $repository->getHome() . "/cache\n";
$repository->purgeCache();
rmdir($repository->getHome() . '/cache');
