<?php
namespace ITRocks\Class_Use\Index;

use PHPUnit\Framework\TestCase;

class Search_Test extends TestCase
{
	use Search;

	//------------------------------------------------------------------------------------------- $by
	protected array $by = [
		T_CLASS => [
			'C' => ['C' => [T_DECLARE_CLASS => ['C.php' => [3 => 2]]]],
			''  => ['C' => [T_NEW => ['C.php' => [10 => 3]]]]
		],
		T_CLASS_TYPE => [
			'C' => [T_DECLARE_CLASS => ['C' => ['C.php' => [3 => 2]]]],
			''  => [T_NEW => ['C' => ['C.php' => [10 => 3]]]]
		],
		T_TYPE_CLASS => [
			T_DECLARE_CLASS => ['C' => ['C' => ['C.php' => [3 => 2]]]],
			T_NEW           => ['' => ['C' => ['C.php' => [10 => 3]]]]
		],
		T_TYPE_USE => [
			T_DECLARE_CLASS => ['C' => ['C' => ['C.php' => [3 => 2]]]],
			T_NEW           => ['C' => ['' => ['C.php' => [10 => 3]]]]
		],
		T_USE => ['C' => [
			'C' => [T_DECLARE_CLASS => ['C.php' => [3 => 2]]],
			''  => [T_NEW           => ['C.php' => [10 => 3]]]
		]],
		T_USE_TYPE => ['C' => [
			T_DECLARE_CLASS => ['C' => ['C.php' => [3 => 2]]],
			T_NEW           => ['' => ['C.php' => [10 => 3]]]
		]]
	];

	//--------------------------------------------------------------------------------- cacheFileName
	protected function cacheFileName(string $name, int $type = null) : string
	{
		return __DIR__ . '/cache/cached.json';
	}

	//------------------------------------------------------------------------- testAssociativeString
	public function testAssociativeString() : void
	{
		$actual   = $this->search([T_CLASS => 'C'], T_STRING);
		$expected = [[
			'class'     => 'C',
			'type'      => T_DECLARE_CLASS,
			'use'       => 'C',
			'file'      => 'C.php',
			'line'      => 2,
			'token_key' => 3
		]];
		self::assertEquals($expected, $actual);
	}

	//--------------------------------------------------------------------------- testAssociativeType
	public function testAssociativeType() : void
	{
		foreach ([T_TYPE, true] as $associative) {
			$actual   = $this->search([T_CLASS => 'C'], $associative);
			$expected = [[
				T_CLASS     => 'C',
				T_TYPE      => T_DECLARE_CLASS,
				T_USE       => 'C',
				T_FILE      => 'C.php',
				T_LINE      => 2,
				T_TOKEN_KEY => 3
			]];
			self::assertEquals($expected, $actual);
		}
	}

	//------------------------------------------------------------------------------------- testClass
	public function testClass() : void
	{
		$actual = $this->search([T_CLASS => 'C']);
		self::assertEquals([['C', T_DECLARE_CLASS, 'C', 'C.php', 2, 3]], $actual, 'C');

		$actual = $this->search([T_CLASS => 'D']);
		self::assertEquals([], $actual, 'D');

		$actual = $this->search([T_CLASS => '']);
		self::assertEquals([['', T_NEW, 'C', 'C.php', 3, 10]], $actual, 'out');
	}

	//--------------------------------------------------------------------------------- testClassFile
	public function testClassFile() : void
	{
		$actual = $this->search([T_CLASS => 'C', T_FILE => 'C.php']);
		self::assertEquals([['C', T_DECLARE_CLASS, 'C', 'C.php', 2, 3]], $actual, 'T_DECLARE_CLASS');

		$actual = $this->search([T_CLASS => '', T_FILE => 'C.php']);
		self::assertEquals([['', T_NEW, 'C', 'C.php', 3, 10]], $actual, 'T_NEW');

		$actual = $this->search([T_CLASS => 'C', T_FILE => 'D.php']);
		self::assertEquals([], $actual, 'D>C');

		$actual = $this->search([T_CLASS => '', T_FILE => 'D.php']);
		self::assertEquals([], $actual, 'D>');
	}

	//--------------------------------------------------------------------------------- testClassType
	public function testClassType() : void
	{
		$actual = $this->search([T_CLASS => 'C', T_TYPE => T_DECLARE_CLASS]);
		self::assertEquals([['C', T_DECLARE_CLASS, 'C', 'C.php', 2, 3]], $actual, 'T_DECLARE_CLASS');

		$actual = $this->search([T_CLASS => '', T_TYPE => T_NEW]);
		self::assertEquals([['', T_NEW, 'C', 'C.php', 3, 10]], $actual, 'T_NEW');

		$actual = $this->search([T_CLASS => 'C', T_TYPE => T_NEW]);
		self::assertEquals([], $actual, 'C::T_NEW');

		$actual = $this->search([T_CLASS => 'C', T_TYPE => T_STATIC]);
		self::assertEquals([], $actual, 'T_STATIC');
	}

	//------------------------------------------------------------------------------ testClassTypeUse
	public function testClassTypeUse() : void
	{
		$actual = $this->search([T_CLASS => 'C', T_TYPE => T_DECLARE_CLASS, T_USE => 'C']);
		self::assertEquals([['C', T_DECLARE_CLASS, 'C', 'C.php', 2, 3]], $actual, 'T_DECLARE_CLASS');

		$actual = $this->search([T_CLASS => '', T_TYPE => T_NEW, T_USE => 'C']);
		self::assertEquals([['', T_NEW, 'C', 'C.php', 3, 10]], $actual, 'T_NEW');

		$actual = $this->search([T_CLASS => 'C', T_TYPE => T_NEW, T_USE => 'C']);
		self::assertEquals([], $actual, 'C::T_NEW');

		$actual = $this->search([T_CLASS => '', T_TYPE => T_NEW, T_USE => 'D']);
		self::assertEquals([], $actual, 'T_NEW(D)');

		$actual = $this->search([T_CLASS => 'C', T_TYPE => T_DECLARE_CLASS, T_USE => 'D']);
		self::assertEquals([], $actual, 'T_DECLARE_CLASS(D)');
	}

	//------------------------------------------------------------------------------------- testEmpty
	public function testEmpty() : void
	{
		$actual = $this->search([]);
		self::assertEquals([], $actual, 'empty');

		$actual = $this->search([T_FILE => 'C.php']);
		self::assertEquals([], $actual, 'T_FILE');
	}

	//-------------------------------------------------------------------------------------- testLoad
	public function testLoad() : void
	{
		if (!is_dir(__DIR__ . '/cache')) {
			mkdir(__DIR__ . '/cache');
		}
		file_put_contents($this->cacheFileName(''), json_encode($this->by[T_USE]['C']));
		unset($this->by[T_USE]['C']);

		$actual   = $this->search([T_USE => 'C']);
		$expected = [
			['C', T_DECLARE_CLASS, 'C', 'C.php', 2, 3],
			['',  T_NEW,           'C', 'C.php', 3, 10]
		];
		self::assertEquals($expected, $actual, 'search');

		$expected = [
			'C' => [T_DECLARE_CLASS => ['C.php' => [3 => 2]]],
			''  => [T_NEW           => ['C.php' => [10 => 3]]]
		];
		self::assertEquals($expected, $this->by[T_USE]['C'], 'by');

		unlink($this->cacheFileName(''));
		rmdir(__DIR__ . '/cache');
	}

	//-------------------------------------------------------------------------------------- testType
	public function testType() : void
	{
		$actual = $this->search([T_TYPE => T_DECLARE_CLASS]);
		self::assertEquals([['C', T_DECLARE_CLASS, 'C', 'C.php', 2, 3]], $actual, 'T_DECLARE_CLASS');

		$actual = $this->search([T_TYPE => T_NEW]);
		self::assertEquals([['', T_NEW, 'C', 'C.php', 3, 10]], $actual, 'T_NEW');

		$actual = $this->search([T_TYPE => T_STATIC]);
		self::assertEquals([], $actual, 'T_STATIC');
	}

	//----------------------------------------------------------------------------------- testTypeUse
	public function testTypeUse() : void
	{
		$actual = $this->search([T_TYPE => T_DECLARE_CLASS, T_USE => 'C']);
		self::assertEquals([['C', T_DECLARE_CLASS, 'C', 'C.php', 2, 3]], $actual, 'T_DECLARE_CLASS');

		$actual = $this->search([T_TYPE => T_NEW, T_USE => 'C']);
		self::assertEquals([['', T_NEW, 'C', 'C.php', 3, 10]], $actual, 'T_NEW');

		$actual = $this->search([T_TYPE => T_STATIC, T_USE => 'C']);
		self::assertEquals([], $actual, 'T_STATIC');

		$actual = $this->search([T_TYPE => T_NEW, T_USE => 'D']);
		self::assertEquals([], $actual, 'D');
	}

	//--------------------------------------------------------------------------------------- testUse
	public function testUse() : void
	{
		$actual   = $this->search([T_USE => 'C']);
		$expected = [
			['C', T_DECLARE_CLASS, 'C', 'C.php', 2, 3],
			['',  T_NEW,           'C', 'C.php', 3, 10]
		];
		self::assertEquals($expected, $actual, 'C');

		$actual = $this->search([T_USE => 'D']);
		self::assertEquals([], $actual, 'D');
	}

	//----------------------------------------------------------------------------------- testUseFile
	public function testUseFile() : void
	{
		$actual   = $this->search([T_FILE => 'C.php', T_USE => 'C']);
		$expected = [
			['C', T_DECLARE_CLASS, 'C', 'C.php', 2, 3],
			['',  T_NEW,           'C', 'C.php', 3, 10]
		];
		self::assertEquals($expected, $actual, 'C');

		$actual = $this->search([T_FILE => 'C.php', T_USE => 'D']);
		self::assertEquals([], $actual, 'D');

		$actual = $this->search([T_FILE => 'D.php', T_USE => 'C']);
		self::assertEquals([], $actual, 'D>C');
	}

}
