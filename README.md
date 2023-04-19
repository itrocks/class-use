PHP class dependency scanner 
----------------------------

This library offers a programming API and command lines to scan your PHP project for class
references, store them into an indexed dependency cache into your filesystem,
then allow you to search for all available dependency of a class.

Installation
------------

### Add to your project

```bash
composer require itrocks/depend --dev
```

### Global command line

This allows you to use the global command `depend` from your PHP projet directory,
without having to look for the executable.

```bash
sudo ln -s /path/to/your/project/vendor/itrocks/depend/bin/run /usr/local/bin/depend 
```

Command line usage
------------------

### Installation

```bash
git clone itrocks/depend
# make it available from any directory 
sudo ln -s `pwd`/depend/bin/run /usr/local/bin/depend
```

Command line is available for usage demonstration purpose and benchmarking:
it displays operation duration, memory usage, and counters.
It makes it easy to test the library without having to write source code.

### Update cache

From any subdirectory of your PHP project (e.g. here from the project root directory):

```bash
depend vendor
```

#### Options

- `vendor`: Updates dependency concerning php files from the `vendor` directory too. If not set,
  only your project files will be scanned, which will be quite faster, and enough only if you don't
  need third-party PHP scripts dependency information. 
- `reset`: Fully recalculate your dependency cache. If not set, only files modified since the last
  update will be scanned for update.   
- `pretty`: Dependency cache json files will be human-readable, including spaces and carriage
  returns. Nevertheless, cache files will be bigger.

### Search for occurrences

From any directory of your PHP project (e.g. here from the project root directory):

```bash
depend dependency=ITRocks\Depend\Repository detail
```

This example will output all the references to the class Repository into all your project scripts.

#### Search keys

You can use one of several search criterion, identified by these keys:

- `class`: Restrict search to this class only
- `dependency`: Search references to this class 
- `type`: Search references of this type (type lists below)

#### Options

These options can be added to your command line:

- `display`: Display found dependencies. If not set, only the search information:
duration, memory usage, result count, will be displayed

Programming API usage
---------------------

*Documentation will come soon*.

Types
-----

Each found dependency is qualified with any of these type identifiers:

- `argument`: the class is used as a function argument type
- `attribute`: the class is used as an attribute, ie into a `#[...]` section
- `class`: the dependency gets the name of the class, ie `Class_Name::class`
- `declare-class`: the class declaration
- `declare-interface`: the interface declaration
- `declare-trait`: the trait declaration
- `extends`: the class appears into an `extends` section of another class declaration
- `implements`: the class appears into an `implements` section of another class declaration
- `instance-of`: the class appears after an `instanceof` type operator
- `namespace`: the class is used as a `namespace` name; perhaps it's not a class name,
   but just a namespace declaration
- `namespace-use`: the class appears into a namespace `use` statement for import;
   perhaps it's not a class name, but just a namespace import
- `new`: the class is used to instantiate an object
- `return`: the class is used into a function return type
- `static`: the class is used for a static call,
   e.g. `Class_Name::static` or `Class_Name::CONSTANT`
- `use`: the class appears into a class `use` statement
- `var`: the class is used into a class property type definition

Attribute names are used as types too : every reference to a class into an attribute will be
referred as a dependency which type is the attribute name.
