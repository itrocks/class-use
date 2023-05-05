<?php
namespace ITRocks\Class_Use;
// phpcs:ignoreFile

use Exception;
use ITRocks\Class_Use\Token\Name;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

class Console_Test extends TestCase
{

	//----------------------------------------------------------------------------- testNameArguments
	/**
	 * @noinspection PhpDocMissingThrowsInspection
	 * @param string[]                 $arguments
	 * @param array<int|string,string> $expected
	 */
	#[TestWith(['A', ['pretty'], ['pretty']])]
	#[TestWith(['B', ['RESET'], ['reset']])]
	#[TestWith(['C', ['home', '=', '/home/project'], ['home' => '/home/project']])]
	#[TestWith(['D', ['home', '=/home/project'],     ['home' => '/home/project']])]
	#[TestWith(['E', ['home=', '/home/project'],     ['home' => '/home/project']])]
	#[TestWith(['F', ['home=/home/project'],         ['home' => '/home/project']])]
	#[TestWith(['G', ['prefix', 'home', '=', '/home/pro'], ['prefix', 'home' => '/home/pro']])]
	#[TestWith(['H', ['prefix', 'home', '=/home/pro'],     ['prefix', 'home' => '/home/pro']])]
	#[TestWith(['I', ['prefix', 'home=', '/home/pro'],     ['prefix', 'home' => '/home/pro']])]
	#[TestWith(['J', ['prefix', 'home=/home/pro'],         ['prefix', 'home' => '/home/pro']])]
	#[TestWith(['K', ['home', '=', '/home/pro', 'reset'], ['home' => '/home/pro', 3 => 'reset']])]
	#[TestWith(['L', ['home', '=', '/Home/Project', 'reset'], ['home' => '/Home/Project', 3 => 'reset']])]
	#[TestWith(['M', ['home', '=/home/project', 'reset'], ['home' => '/home/project', 2 => 'reset']])]
	#[TestWith(['N', ['home=', '/home/project', 'reset'], ['home' => '/home/project', 2 => 'reset']])]
	#[TestWith(['O', ['home=/home/project', 'RESET'], ['home' => '/home/project', 1 => 'reset']])]
	public function testNameArguments(string $key, array $arguments, array $expected) : void
	{
		$console        = new Console;
		$name_arguments = new ReflectionMethod(Console::class, 'nameArguments');
		/** @noinspection PhpUnhandledExceptionInspection Valid call */
		$name_arguments->invokeArgs($console, [&$arguments]);
		self::assertEquals($expected, $arguments, $key);
	}

	//------------------------------------------------------------------------ testNameArgumentsClass
	/**
	 * @noinspection PhpDocMissingThrowsInspection
	 * @param string[]             $arguments
	 * @param array<string,string> $expected
	 */
	#[TestWith([['class=Vendor/Project/Class'], ['class' => 'Vendor\Project\Class']])]
	#[TestWith([['home=Vendor/Project/Class'],  ['home'  => 'Vendor/Project/Class']])]
	#[TestWith([['type=Vendor/Project/Class'],  ['type'  => 'Vendor\Project\Class']])]
	#[TestWith([['use=Vendor/Project/Class'],   ['use'   => 'Vendor\Project\Class']])]
	#[TestWith([['Home=Vendor/Project/Class'],  ['home'  => 'Vendor/Project/Class']])]
	#[TestWith([['USE=Vendor/Project/Class'],   ['use'   => 'Vendor\Project\Class']])]
	public function testNameArgumentsClass(array $arguments, array $expected) : void
	{
		$console        = new Console;
		$name_arguments = new ReflectionMethod(Console::class, 'nameArguments');
		/** @noinspection PhpUnhandledExceptionInspection Valid call */
		$name_arguments->invokeArgs($console, [&$arguments]);
		self::assertEquals($expected, $arguments);
	}

	//---------------------------------------------------------------------------------- testNewIndex
	public function testNewIndex() : void
	{
		$console   = new Console;
		$new_index = new ReflectionMethod(Console::class, 'newIndex');
		/** @noinspection PhpUnhandledExceptionInspection Valid call */
		$index = $new_index->invoke($console);
		self::assertInstanceOf(Index::class, $index);
	}

	//------------------------------------------------------------------------ testQuickDocumentation
	public function testQuickDocumentation() : void
	{
		$console = new Console;
		$buffer  = $console->quickDocumentation();
		self::assertTrue(strlen($buffer) > 0);
	}

	//----------------------------------------------------------------------------------- testRunScan
	/**
	 * @param string[] $arguments
	 * @throws Exception
	 */
	#[TestWith([0, []])]
	#[TestWith([1, ['anything']])]
	#[TestWith([2, ['pretty']])]
	#[TestWith([3, ['reset']])]
	#[TestWith([4, ['vendor']])]
	#[TestWith([5, ['pretty', 'reset']])]
	public function testRunScan(int $index, array $arguments) : void
	{
		$model = new class extends Console {
			/** @var array<string,bool> */
			public array $call = ['scan' => false, 'search' => false];
			/** @param array<int|string,string> $arguments */
			protected function scan  (array $arguments) : void { $this->call[__FUNCTION__] = true; }
			/** @param array<int|string,string> $arguments */
			protected function search(array $arguments) : void { $this->call[__FUNCTION__] = true; }
		};
		$console = clone $model;
		$console->run($arguments);
		self::assertEquals(['scan' => true, 'search' => false], $console->call, strval($index));
	}

	//--------------------------------------------------------------------------------- testRunSearch
	/** @throws Exception */
	public function testRunSearch() : void
	{
		$model = new class extends Console {
			/** @var array<string,bool> */
			public array $call = ['scan' => false, 'search' => false];
			/** @param array<int|string,string> $arguments */
			protected function scan  (array $arguments) : void { $this->call[__FUNCTION__] = true; }
			/** @param array<int|string,string> $arguments */
			protected function search(array $arguments) : void { $this->call[__FUNCTION__] = true; }
		};
		foreach (Index::SAVE as $search) {
			$search  = Name::OF[$search];
			$console = clone $model;
			$console->run([$search => __CLASS__]);
			self::assertEquals(['scan' => false, 'search' => true], $console->call, $search);
		}
	}

	//-------------------------------------------------------------------------------------- testScan
	/**
	 * @noinspection PhpDocMissingThrowsInspection
	 * @param string[] $arguments
	 * @param string[] $expected_flags
	 */
	#[TestWith([1, [], []])]
	#[TestWith([2, [Console::PRETTY], [Console::PRETTY]])]
	#[TestWith([3, [Console::RESET], [Console::RESET]])]
	#[TestWith([4, [Console::VENDOR], [Console::VENDOR]])]
	#[TestWith([5, [Console::PRETTY, Console::RESET], [Console::PRETTY, Console::RESET]])]
	public function testScan(int $key, array $arguments, array $expected_flags) : void
	{
		$console = new class extends Console {
			public Index $index;
			protected function newIndex(int $flags = 0, string $home = '') : Index {
				return $this->index = new class($flags, $home) extends Index {
					/** @var array<string,int> */
					public array $call = [
						'classify' => 0, 'prepareHome' => 0, 'save' => 0, 'scanDirectory' => 0
					];
					protected int $order = 0;
					public    int $saved_files_count = 0;
					public function classify()       : void { $this->call[__FUNCTION__] = ++$this->order; }
					protected function prepareHome() : void { $this->call[__FUNCTION__] = ++$this->order; }
					public function save()           : void { $this->call[__FUNCTION__] = ++$this->order; }
					public function scanDirectory(string $directory = '', int $depth = 0) : void {
						$this->call[__FUNCTION__] = ++$this->order;
					}
				};
			}
		};
		$display = '';
		ob_start(function(string $buffer) use(&$display) { $display .= $buffer; });
		/** @noinspection PhpUnhandledExceptionInspection Valid method */
		$scan = new ReflectionMethod($console, 'scan');
		/** @noinspection PhpUnhandledExceptionInspection Valid call */
		$scan->invoke($console, $arguments);
		ob_end_clean();
		/** @noinspection PhpPossiblePolymorphicInvocationInspection defined by anonymous */
		self::assertEquals(
			['prepareHome' => 1, 'scanDirectory' => 2, 'classify' => 3, 'save' => 4],
			$console->index->call, /** @phpstan-ignore-line defined by anonymous */
			$key . ':call'
		);
		foreach ([Console::PRETTY, Console::RESET, Console::VENDOR] as $flag) {
			$expected_flag = in_array($flag, $expected_flags, true);
			/** @noinspection PhpUnhandledExceptionInspection $flag is a valid property name */
			$property = new ReflectionProperty(Index::class, $flag);
			self::assertEquals($expected_flag, $property->getValue($console->index), $key . ':' . $flag);
			if ($expected_flag) {
				self::assertStringContainsString($flag, $display, $key . ':display ' . $flag);
			}
		}
	}

	//------------------------------------------------------------------------------------ testSearch
	/**
	 * @noinspection PhpDocMissingThrowsInspection
	 * @param array<string, int|string> $arguments
	 * @param array<int, int|string>    $expected_search
	 */
	#[TestWith([1, [], []])]
	#[TestWith([2, ['class' => __CLASS__],       [T_CLASS => __CLASS__]])]
	#[TestWith([3, ['use'   => __CLASS__],       [T_USE   => __CLASS__]])]
	#[TestWith([4, ['class' => __CLASS__, 'use' => 'An\O'], [T_CLASS => __CLASS__, T_USE => 'An\O']])]
	#[TestWith([5, [Console::ASSOCIATIVE], [], '[["A",318,"B","f.php",1,2]]', T_STRING])]
	#[TestWith([6, [Console::DATA], [], '[["A",318,"B","f.php",1,2]]'])]
	#[TestWith([7, [Console::TOTAL], []])]
	#[TestWith([11, ['type' => 'declare-class'], [T_TYPE  => T_DECLARE_CLASS]])]
	#[TestWith([12, ['type' => 'declare_trait'], [T_TYPE  => T_DECLARE_TRAIT]])]
	#[TestWith([13, ['type' => 'T_NEW'],         [T_TYPE  => T_NEW]])]
	#[TestWith([14, ['type' => T_INSTANCEOF],    [T_TYPE  => T_INSTANCEOF]])]
	#[TestWith([15, ['type' => 'T_BAD'],         [T_TYPE  => 'T_BAD']])]
	#[TestWith([16, ['type' => 'A\Class'],       [T_TYPE  => 'A\Class']])]
	#[TestWith([16, ['type' => 9999],            [T_TYPE  => 9999]])]
	#[TestWith([21, [Console::DATA, Console::TOTAL], [], '[["A",318,"B","f.php",1,2]]\n1 results\n'])]
	#[TestWith([22, [Console::PRETTY], [], '[\n    [\n        "A",\n        318,\n        "B",\n        "f.php",\n        1,\n        2\n    ]\n]'])]
	#[TestWith([23, [Console::DATA, Console::PRETTY], [],
		'[\n    [\n        "A",\n        318,\n        "B",\n        "f.php",\n        1,\n        2\n    ]\n]'
	])]
	#[TestWith([24, [Console::PRETTY, Console::TOTAL], [],
		'[\n    [\n        "A",\n        318,\n        "B",\n        "f.php",\n        1,\n        2\n    ]\n]\n1 results\n'
	])]
	#[TestWith([31, [Console::DATA, Console::PRETTY, Console::TOTAL], [],
		'[\n    [\n        "A",\n        318,\n        "B",\n        "f.php",\n        1,\n        2\n    ]\n]\n1 results\n'
	])]
	#[TestWith([32, [Console::BENCHMARK], [],
		"1 results\n(duration [2-7]? = [0-9]+(\.[0-9]+)? ms\n){7}memory = [0-9]+ Mo\n"
	])]
	#[TestWith([33, [Console::BENCHMARK, Console::TOTAL], [],
		"1 results\n(duration [2-7]? = [0-9]+(\.[0-9]+)? ms\n){7}memory = [0-9]+ Mo\n"
	])]
	#[TestWith([34, [Console::BENCHMARK, Console::DATA], [],
		'\[\["A",318,"B","f.php",1,2]]\n(duration [2-7]? = [0-9]+(\.[0-9]+)? ms\n){7}memory = [0-9]+ Mo\n'
	])]
	#[TestWith([35, [Console::BENCHMARK, Console::DATA, Console::TOTAL], [],
		'\[\["A",318,"B","f.php",1,2]]\n1 results\n(duration [2-7]? = [0-9]+(\.[0-9]+)? ms\n){7}memory = [0-9]+ Mo\n'
	])]
	#[TestWith([36, [Console::BENCHMARK, Console::DATA, Console::PRETTY, Console::TOTAL], [],
		'\[\n    \[\n        "A",\n        318,\n        "B",\n        "f.php",\n        1,\n        2\n    ]\n]\n1 results\n(duration [2-7]? = [0-9]+(\.[0-9]+)? ms\n){7}memory = [0-9]+ Mo\n'
	])]
	#[TestWith([37,
		['class' => __CLASS__, 'type' => 'new', 'use' => 'An\O', Console::ASSOCIATIVE, Console::DATA],
		[T_CLASS => __CLASS__, T_TYPE => T_NEW, T_USE => 'An\O'],
		'[["A",318,"B","f.php",1,2]]',
		T_STRING
	])]
	public function testSearch(
		int|string $key, array $arguments, array $expected_search, string $expected_display = null,
		bool|int $expected_associative = false
	) : void
	{
		$console = new class extends Console {
			public Index $index;
			protected function newIndex(int $flags = 0, string $home = '') : Index {
				return $this->index = new class($flags, $home) extends Index {
					public bool|int|null $associative = null;
					/** @var array<string,bool> */
					public array $call = ['search' => false];
					/** @var array<int,int|string>|null */
					public ?array $search = null;
					public function search(array $search, bool|int $associative = false) : array {
						$this->call[__FUNCTION__] = true;
						$this->associative        = $associative;
						$this->search             = $search;
						return [['A', T_USE, 'B', 'f.php', 1, 2]];
					}
				};
			}
		};
		/** @noinspection PhpUnhandledExceptionInspection Valid method */
		$search  = new ReflectionMethod($console, 'search');
		$display = '';
		ob_start(function(string $buffer) use(&$display) { $display .= $buffer; });
		/** @noinspection PhpUnhandledExceptionInspection Valid call */
		$search->invoke($console, $arguments);
		ob_end_clean();
		/** @noinspection PhpPossiblePolymorphicInvocationInspection defined by anonymous */
		self::assertEquals(
			$expected_associative,
			$console->index->associative, /** @phpstan-ignore-line defined by anonymous */
			$key . ':associative'
		);
		/** @noinspection PhpPossiblePolymorphicInvocationInspection defined by anonymous */
		self::assertEquals(
			['search' => true],
			$console->index->call, /** @phpstan-ignore-line defined by anonymous */
			$key . ':call'
		);
		/** @noinspection PhpPossiblePolymorphicInvocationInspection defined by anonymous */
		self::assertEquals(
			$expected_search,
			$console->index->search, /** @phpstan-ignore-line defined by anonymous */
			$key . ':search'
		);
		if ($expected_display && str_contains($expected_display, '+')) {
			self::assertMatchesRegularExpression(
				'/\G' . $expected_display . '\z/', $display, $key . ':display'
			);
		}
		else {
			self::assertEquals(
				str_replace('\n', "\n", $expected_display ?? "1 results\n"), $display, $key . ':display'
			);
		}
	}

	//------------------------------------------------------------------------------ testShowDuration
	/** @noinspection PhpUnhandledExceptionInspection Valid call */
	public function testShowDuration() : void
	{
		$console = new Console;
		$show_duration = new ReflectionMethod(Console::class, 'showDuration');
		self::assertEquals('1.25 seconds', $show_duration->invoke($console, 1.25012));
		self::assertEquals('0.1 seconds',  $show_duration->invoke($console, .1004));
		self::assertEquals('99.999 ms',    $show_duration->invoke($console, .0999992));
		self::assertEquals('99.99 ms',     $show_duration->invoke($console, .099992, 2));
		self::assertEquals('100 ms',       $show_duration->invoke($console, .099995, 2));
		self::assertEquals('99 ms',        $show_duration->invoke($console, .0990002));
	}

}
