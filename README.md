PHP class dependency scanner 
----------------------------

This library gives your software the ability to get a quick answer to the
**"Where and what for is this class used ?"** question.

It offers a programming API and command lines to scan your PHP project for class
references, store them into an indexed dependency cache into your filesystem,
then allow you to search for all available dependency of a class.

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
git clone itrocks/depend

# make it available from any directory (Linux systems):
sudo ln -s `pwd`/depend/bin/run /usr/local/bin/depend
```

### Update cache

From any subdirectory of your PHP project (e.g. here from the project root directory):

```bash
depend vendor

# OR: if you did not add a direct link to the console script, you can do the same with (Linux systems):
/path/to/your/clone/of/itrocks/depend/bin/run vendor

# OR: if you have php installed on a non-Linux system (e.g. Windows):
php /path/to/your/clone/of/itrocks/depend/bin/run.php vendor
```

#### Options

- `vendor`:
  Updates dependency concerning php files from the `vendor` directory too. If not set,
  only your project files will be scanned, which will be quite faster, and enough only if you don't
  need third-party PHP scripts dependency information.
- `reset`:
  Fully recalculate your dependency cache. If not set, only files modified since the last update
  will be scanned for update.
- `pretty`:
  Dependency cache json files will be human-readable, including spaces and carriage returns;
  Nevertheless, cache files will be bigger.

### Search for occurences

From any directory of your PHP project (e.g. here from the project root directory):

```bash
depend dependency=ITRocks/Depend/Repository detail
```

This example will output all the references to the class Repository into all your project scripts.

If you love escaping antislashes, you are free to use them to match PHP class path naming rules.

#### Search keys

You can use one or several search criterion, identified by these keys:

- `class`: Search references into this class
- `dependency`: Search references to this class 
- `type`: Search references of this type (type lists below)

#### Options

These options can be added to your command line:

- `display`: Display found dependencies. If not set, only the search information:
  duration, memory usage, result count, will be displayed

Programming API usage
---------------------

### Installation

To add this to your project :

```bash
composer require itrocks/depend
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
  Updates dependency concerning php files from the `vendor` directory too. If not set,
  only your project files will be scanned, which will be quite faster, and enough only if you don't
  need third-party PHP scripts dependency information.
- `Repository::RESET`:
  Fully recalculate your dependency cache. If not set, only files modified since the last update
  will be scanned for update.
- `Repository::PRETTY`:
  Dependency cache json files will be human-readable, including spaces and carriage returns;
  Nevertheless, cache files will be bigger.

The constructor of `Repository` has a second argument, `$home`, to force the directory where to
scan classes and save dependency cache from. If not set, this will scan your project files, found
from the current working directory.

### Search for occurences

```php
echo "These are all references to the Repository class:\n";
$repository = new Repository();
foreach ($repository->search([Type::DEPENDENCY => Repository::class]) as $dependency) {
  [$class, $dependency, $type, $file, $line] = $dependency;
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

Types
-----

Each found dependency is qualified with any of these dependency type identifiers:

- `argument`: the class is used as a function argument type
- `attribute`: the class is used as an attribute, ie into a `#[...]` section
- `class`: the dependency gets the name of the class, ie `Class_Name::class`
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
referred as a dependency which type is the attribute name.
