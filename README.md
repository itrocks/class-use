PHP class use scanner 
---------------------

This library gives your software the ability to get a quick answer to the
**"Where and what for is this class used ?"** question.

It offers a programming API and command lines to scan your PHP project for class
references, store them into an indexed class use cache into your filesystem,
then allow you to search where a class is used into your whole project.

It is designed to be simple to apply to any PHP project, and fast to execute.
Its code aims for simplicity and lightweight: no library dependency,
and I tried to limit it to the fewer abstraction layers I could
in order to get a code simple to understand and to update.

Pre-requisites
--------------

- This works with PHP 8.2 only. I wanted to make full use of the current PHP version features. \
  To install php 8.2 on a Debian/Ubuntu/Mint system:
  https://php.watch/articles/install-php82-ubuntu-debian.
- You will need the enable PHP json extension.
  On Linux Debian/Ubuntu/Mint systems:
```bash
sudo apt install php8.2-json
```
- OR, on Windows systems, just uncomment the `extension=json.so` line into your php.ini file.

Command line usage
------------------

Command line is available for usage demonstration purpose and benchmarking:
it displays operation duration, memory usage, and counters.
It makes it easy to test the library without having to write source code.

### Installation

```bash
git clone itrocks/class-use

# make it available from any directory (Linux systems):
sudo ln -s `pwd`/class-use/bin/run /usr/local/bin/class-use
```

### Update cache

From any subdirectory of your PHP project (e.g. here from the project root directory):

```bash
class-use vendor

# OR: if you did not add a direct link to the console script, you can do the same with (Linux systems):
/path/to/your/clone/of/itrocks/class-use/bin/run vendor

# OR: if you have php installed on a non-Linux system (e.g. Windows):
php /path/to/your/clone/of/itrocks/class-use/bin/run.php vendor
```

#### Options

- `vendor`:
  Updates class uses concerning php files from the `vendor` directory too. If not set,
  only your project files will be scanned, which will be quite faster, and enough only if you don't
  need third-party PHP scripts class use information.
- `reset`:
  Fully recalculate your class use cache. If not set, only files modified since the last update
  will be scanned for update.
- `pretty`:
  Class use cache json files will be human-readable, including spaces and carriage returns;
  Nevertheless, cache files will be bigger.

### Search for occurrences

From any directory of your PHP project (e.g. here from the project root directory):

```bash
class-use use=ITRocks/Class_Use/Repository detail
```

This example will output all uses of the class Repository into all your project scripts.

If you love escaping antislashes, feel free to type them to match PHP class path naming rules.

#### Search keys

You can use one or several search criterion, identified by these keys:

- `class`: Search class uses into this class source code
- `use`: Search class uses of this class, everywhere 
- `type`: Search class uses of this type (type lists below)

#### Options

These options can be added to your command line:

- `display`: Display found class uses. If not set, only the search information:
  duration, memory usage, result count, will be displayed

Programming API usage
---------------------

### Installation

To add this to your project :

```bash
composer require itrocks/class-use
```

### Update cache

When your project need to update the cache, these are the update steps to follow :

```php
$repository = new Repository(Repository::VENDOR);
$repository->scanDirectory();
$repository->classify();
$repository->save();
```

#### Option flags

- `Repository::VENDOR`:
  Updates class use cache concerning php files from the `vendor` directory too. If not set,
  only your project files will be scanned, which will be quite faster, and enough only if you don't
  need third-party PHP scripts class use information.
- `Repository::RESET`:
  Fully recalculate your class use cache. If not set, only files modified since the last update
  will be scanned for update.
- `Repository::PRETTY`:
  Class use cache json files will be human-readable, including spaces and carriage returns;
  Nevertheless, cache files will be bigger.

The constructor of `Repository` has a second argument, `$home`, to force the directory where to
scan classes and save class use cache from. If not set, this will scan your project files, found
from the current working directory.

### Search for occurences

```php
echo "These are where the Repository class is used:\n";
$repository = new Repository();
foreach ($repository->search([Type::USE => Repository::class]) as $reference) {
  [$class, $use, $type, $file, $line] = $reference;
	echo "#$key. $type into $file";
	if ($class) echo " class $class";
	echo " line $line";
	echo "\n";
}
```

This example will output all the references to the class Repository into all your project scripts.

You can see multiple examples in action running the scripts into the `examples` directory. e.g.:

```bash
php examples/complete.php
```

Class use Types
---------------

Each found reference to the class use is qualified with any of these class use type identifiers:

- `argument`: the class is used as a function argument type
- `attribute`: the class is used as an attribute, ie into a `#[...]` section
- `class`: an explicit use of the name of the class into the source code, ie `Class_Name::class`
- `declare-class`: the class declaration
- `declare-interface`: the interface declaration
- `declare-trait`: the trait declaration
- `extends`: the class appears into an `extends` section of another class declaration
- `implements`: the class appears into an `implements` section of another class declaration
- `instance-of`: the class appears after an `instanceof` type operator
- `namespace`: the class is used as a `namespace` name;
  only namespaces that match a class will be set
- `namespace-use`: the class appears into a namespace `use` statement for import;
  only statements that match a class will be set
- `new`: the class is used to instantiate an object
- `return`: the class is used into a function return type
- `static`: the class is used for a static call,
  e.g. `Class_Name::static` or `Class_Name::CONSTANT`
- `use`: the class appears into a class `use` statement
- `var`: the class is used into a class property type definition

Attribute names are used as types too : every reference to a class into an attribute will be
referred as a class use reference which type is the name of the attribute.
