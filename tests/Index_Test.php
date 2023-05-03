<?php
namespace ITRocks\Class_Use;

use Attribute;
use Exception;
use ITRocks\Class_Use\Index\Test_Common;
use ITRocks\Class_Use\Tests\Index\Load_And_Filter\A;
use ITRocks\Class_Use\Tests\Index\Load_And_Filter\C;
use ITRocks\Class_Use\Tests\Index\Load_And_Filter\E;
use ITRocks\Class_Use\Tests\Index\Load_And_Filter\F;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

include_once __DIR__ . '/Index/Test_Common.php';

class Index_Test extends TestCase
{
	use Test_Common;

	//-------------------------------------------------------------------------- COMPLETE_EXPECTATION
	const COMPLETE_EXPECTATION = [
		'/class/ITRocks-Class_Use-Tests-Index-Load_And_Filter-A.json' => [
			Attribute::class => [T_ATTRIBUTE     => ['A.php' => [12 => 6]]],
			A::class         => [T_DECLARE_CLASS => ['A.php' => [17 => 7]]]
		],
		'/class/ITRocks-Class_Use-Tests-Index-Load_And_Filter-C.json' => [
			A::class => [T_ATTRIBUTE     => ['C.php' => [7 => 4]]],
			E::class => [A::class        => ['C.php' => [9 => 4]], T_EXTENDS => ['C.php' => [21 => 5]]],
			C::class => [T_DECLARE_CLASS => ['C.php' => [17 => 5]]]
		],
		'/class/ITRocks-Class_Use-Tests-Index-Load_And_Filter-E.json' => [
			E::class => [T_DECLARE_CLASS => ['E.php' => [8 => 4]]]
		],
		'/class/ITRocks-Class_Use-Tests-Index-Load_And_Filter-F.json' => [
			F::class => [T_DECLARE_CLASS => ['F.php' => [8 => 4]]]
		],
		'/class-type/ITRocks-Class_Use-Tests-Index-Load_And_Filter-A.json' => [
			T_ATTRIBUTE     => [Attribute::class => ['A.php' => [12 => 6]]],
			T_DECLARE_CLASS => [A::class         => ['A.php' => [17 => 7]]]
		],
		'/class-type/ITRocks-Class_Use-Tests-Index-Load_And_Filter-C.json' => [
			T_ATTRIBUTE     => [A::class => ['C.php' => [7 => 4]]],
			A::class        => [E::class => ['C.php' => [9 => 4]]],
			T_DECLARE_CLASS => [C::class => ['C.php' => [17 => 5]]],
			T_EXTENDS       => [E::class => ['C.php' => [21 => 5]]]
		],
		'/class-type/ITRocks-Class_Use-Tests-Index-Load_And_Filter-E.json' => [
			T_DECLARE_CLASS => [E::class => ['E.php' => [8 => 4]]]
		],
		'/class-type/ITRocks-Class_Use-Tests-Index-Load_And_Filter-F.json' => [
			T_DECLARE_CLASS => [F::class => ['F.php' => [8 => 4]]]
		],
		'/file/A.json' => [
			T_CLASS => [A::class         => [12 => 6, 17 => 7]],
			T_USE   => [Attribute::class => [12 => 6], A::class => [17 => 7]],
			T_TYPE  => [T_ATTRIBUTE      => [12 => 6], T_DECLARE_CLASS => [17 => 7]]
		],
		'/file/C.json' => [
			T_CLASS => [C::class    => [7 => 4, 9 => 4, 17 => 5, 21 => 5]],
			T_USE   => [A::class    => [7 => 4], E::class => [9 => 4, 21 => 5], C::class => [17 => 5]],
			T_TYPE  => [T_ATTRIBUTE => [7 => 4], A::class => [9 => 4], T_DECLARE_CLASS => [17 => 5], T_EXTENDS => [21 => 5]]
		],
		'/file/E.json' => [
			T_CLASS => [E::class        => [8 => 4]],
			T_USE   => [E::class        => [8 => 4]],
			T_TYPE  => [T_DECLARE_CLASS => [8 => 4]]
		],
		'/file/F.json' => [
			T_CLASS => [F::class        => [8 => 4]],
			T_USE   => [F::class        => [8 => 4]],
			T_TYPE  => [T_DECLARE_CLASS => [8 => 4]]
		],
		'/files.json' => [
			'A.php', 'C.php', 'E.php', 'F.php'
		],
		'/type-class/' . T_EXTENDS . '.json' => [
			C::class => [E::class => ['C.php' => [21 => 5]]]
		],
		'/type-class/' . T_ATTRIBUTE . '.json' => [
			A::class => [Attribute::class => ['A.php' => [12 => 6]]],
			C::class => [A::class         => ['C.php' => [7 => 4]]]
		],
		'/type-class/' . T_DECLARE_CLASS . '.json' => [
			A::class => [A::class => ['A.php' => [17 => 7]]],
			C::class => [C::class => ['C.php' => [17 => 5]]],
			E::class => [E::class => ['E.php' => [8 => 4]]],
			F::class => [F::class => ['F.php' => [8 => 4]]]
		],
		'/type-class/ITRocks-Class_Use-Tests-Index-Load_And_Filter-A.json' => [
			C::class => [E::class => ['C.php' => [9 => 4]]]
		],
		'/type-use/' . T_EXTENDS . '.json' => [
			E::class => [C::class => ['C.php' => [21 => 5]]]
		],
		'/type-use/' . T_ATTRIBUTE . '.json' => [
			Attribute::class => [A::class => ['A.php' => [12 => 6]]],
			A::class         => [C::class => ['C.php' => [7 => 4]]]
		],
		'/type-use/' . T_DECLARE_CLASS . '.json' => [
			A::class => [A::class => ['A.php' => [17 => 7]]],
			C::class => [C::class => ['C.php' => [17 => 5]]],
			E::class => [E::class => ['E.php' => [8 => 4]]],
			F::class => [F::class => ['F.php' => [8 => 4]]]
		],
		'/type-use/ITRocks-Class_Use-Tests-Index-Load_And_Filter-A.json' => [
			E::class => [C::class => ['C.php' => [9 => 4]]]
		],
		'/use/Attribute.json' => [
			A::class => [T_ATTRIBUTE => ['A.php' => [12 => 6]]]
		],
		'/use/ITRocks-Class_Use-Tests-Index-Load_And_Filter-A.json' => [
			A::class => [T_DECLARE_CLASS => ['A.php' => [17 => 7]]],
			C::class => [T_ATTRIBUTE     => ['C.php' => [7 => 4]]]
		],
		'/use/ITRocks-Class_Use-Tests-Index-Load_And_Filter-C.json' => [
			C::class => [T_DECLARE_CLASS => ['C.php' => [17 => 5]]]
		],
		'/use/ITRocks-Class_Use-Tests-Index-Load_And_Filter-E.json' => [
			C::class => [A::class        => ['C.php' => [9 => 4]], T_EXTENDS => ['C.php' => [21 => 5]]],
			E::class => [T_DECLARE_CLASS => ['E.php' => [8 => 4]]]
		],
		'/use/ITRocks-Class_Use-Tests-Index-Load_And_Filter-F.json' => [
			F::class => [T_DECLARE_CLASS => ['F.php' => [8 => 4]]]
		],
		'/use-type/Attribute.json' => [
			T_ATTRIBUTE => [A::class => ['A.php' => [12 => 6]]]
		],
		'/use-type/ITRocks-Class_Use-Tests-Index-Load_And_Filter-A.json' => [
			T_DECLARE_CLASS => [A::class => ['A.php' => [17 => 7]]],
			T_ATTRIBUTE     => [C::class => ['C.php' => [7 => 4]]]
		],
		'/use-type/ITRocks-Class_Use-Tests-Index-Load_And_Filter-C.json' => [
			T_DECLARE_CLASS => [C::class => ['C.php' => [17 => 5]]]
		],
		'/use-type/ITRocks-Class_Use-Tests-Index-Load_And_Filter-E.json' => [
			A::class        => [C::class => ['C.php' => [9 => 4]]],
			T_EXTENDS       => [C::class => ['C.php' => [21 => 5]]],
			T_DECLARE_CLASS => [E::class => ['E.php' => [8 => 4]]]
		],
		'/use-type/ITRocks-Class_Use-Tests-Index-Load_And_Filter-F.json' => [
			T_DECLARE_CLASS => [F::class => ['F.php' => [8 => 4]]]
		]
	];

	//------------------------------------------------------------------- testConstructorDoesNotExist
	public function testConstructorDoesNotExist() : void
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Directory /invalid-aafe42 does not exist');
		new Index(0, '/invalid-aafe42');
	}

	//-------------------------------------------------------------------------- testConstructorFlags
	#[TestWith([1, 0, false, false, false])]
	#[TestWith([2, Index::PRETTY, true, false, false])]
	#[TestWith([3, Index::RESET,  false, true, false])]
	#[TestWith([4, Index::VENDOR, false, false, true])]
	#[TestWith([5, Index::PRETTY | Index::RESET,  true, true, false])]
	#[TestWith([6, Index::PRETTY | Index::VENDOR, true, false, true])]
	#[TestWith([7, Index::RESET  | Index::VENDOR, false, true, true])]
	#[TestWith([8, Index::PRETTY | Index::RESET | Index::VENDOR, true, true, true])]
	public function testConstructorFlags(
		int $key, int $flags, bool $expected_pretty, bool $expected_reset, bool $expected_vendor
	) : void
	{
		$index  = new Index($flags);
		$pretty = new ReflectionProperty(Index::class, 'pretty');
		$reset  = new ReflectionProperty(Index::class, 'reset');
		$vendor = new ReflectionProperty(Index::class, 'vendor');
		self::assertEquals($expected_pretty, $pretty->getValue($index), $key . ':pretty');
		self::assertEquals($expected_reset,  $reset->getValue($index),  $key . ':reset');
		self::assertEquals($expected_vendor, $vendor->getValue($index), $key . ':vendor');
	}

	//---------------------------------------------------------------------- testConstructorNoProject
	public function testConstructorNoProject() : void
	{
		$cwd = getcwd();
		chdir('/home');
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Directory /home does not contain a php project');
		new Index();
		chdir($cwd);
	}

	//-------------------------------------------------------------------------- testConstructorValid
	public function testConstructorValid() : void
	{
		$index  = new Index();
		$pretty = new ReflectionProperty(Index::class, 'pretty');
		$reset  = new ReflectionProperty(Index::class, 'reset');
		$vendor = new ReflectionProperty(Index::class, 'vendor');
		self::assertFalse($pretty->getValue($index), 'pretty');
		self::assertFalse($reset->getValue($index),  'reset');
		self::assertFalse($vendor->getValue($index), 'vendor');
	}

	//------------------------------------------------------------------------------------- testIndex
	public function testIndex() : void
	{
		if (is_dir(__DIR__ . '/Index/Load_And_Filter/cache')) {
			exec('rm -rf "' . __DIR__ . '/Index/Load_And_Filter/cache"');
		}
		$index = new Index(Index::RESET, __DIR__ . '/Index/Load_And_Filter');
		$index->update();
		$actual = [];
		$cache_directory = $index->getCacheDirectory();
		foreach ($this->recurseScanDir($index->getCacheDirectory()) as $file) {
			$actual[$file] = json_decode(file_get_contents($cache_directory . $file), JSON_OBJECT_AS_ARRAY);
		}
		$expected = self::COMPLETE_EXPECTATION;
		self::assertEquals($expected, $actual);
		exec('rm -rf "' . __DIR__ . '/Index/Load_And_Filter/cache"');
	}

	//--------------------------------------------------------------------------------- testSingleton
	public function testSingleton() : void
	{
		$index1 = Index::get();
		$index2 = Index::get();
		self::assertEquals(Index::class, get_class($index1), 'class1');
		self::assertEquals(Index::class, get_class($index2), 'class2');
		self::assertTrue($index1 === $index2, 'equals');
	}

	//------------------------------------------------------------------------------------ testUpdate
	public function testUpdate() : void
	{
		$index = new class extends Index {
			//----------------------------------------------------------------------------------------- $call
			public array $call = ['scanDirectory' => false, 'classify' => false, 'save' => false];
			public function classify() : void {
				$this->call[__FUNCTION__] = true;
			}
			public function save() : void {
				$this->call[__FUNCTION__] = true;
			}
			public function scanDirectory(string $directory = '', int $depth = 0) : void {
				$this->call[__FUNCTION__] = true;
			}
		};
		$index->update();
		self::assertEquals(['scanDirectory' => true, 'classify' => true, 'save' => true], $index->call);
	}

}
