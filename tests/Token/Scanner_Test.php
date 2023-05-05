<?php
namespace ITRocks\Class_Use\Token;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

class Scanner_Test extends TestCase
{

	//----------------------------------------------------------------------- DEFAULT_EXPECTED_VALUES
	protected const DEFAULT_EXPECTED_VALUES = [
		'attribute'             => '',
		'attribute_brackets'    => -1,
		'attribute_parentheses' => -1,
		'braces'                => 0,
		'brackets'              => 0,
		'class'                 => '',
		'class_braces'          => [],
		'namespace'             => '',
		'namespace_braces'      => -1,
		'parentheses'           => 0
	];

	//-------------------------------------------------------------------------------- assertComplete
	/**
	 * @noinspection PhpDocMissingThrowsInspection
	 * @param array<string,array<int>|int|string> $exceptions
	 */
	protected function assertComplete(Scanner $scanner, array $exceptions = []) : void
	{
		foreach (static::DEFAULT_EXPECTED_VALUES as $property_name => $expected) {
			/** @noinspection PhpUnhandledExceptionInspection Would be a unit test error */
			$property = new ReflectionProperty(Scanner::class, $property_name);
			static::assertEquals(
				$exceptions[$property_name] ?? $expected,
				$property->getValue($scanner),
				$this->dataSet() . ' ' . $property_name
			);
		}
	}

	//--------------------------------------------------------------------------------------- dataSet
	protected function dataSet() : string
	{
		$data_name = new ReflectionProperty(parent::class, 'dataName');
		$key       = $data_name->getValue($this);
		return 'data set #' . $key;
	}

	//------------------------------------------------------------------------------- provideBrackets
	/** @return array<array{0:string,1?:array<string,array<int>|int|string>}> */
	public static function provideBrackets() : array
	{
		return [
			// state after complete parsing
			['<?php $v,'                     ],
			['<?php namespace N { }'         ],
			['<?php class C { }'             ],
			['<?php class C implements I { }'],
			['<?php $a[1],'                  ],
			['<?php #[A]'                    ],
			['<?php #[A([1,2],[3]), B([4])]' ],
			['<?php if (1 == 2)'             ],
			['<?php if (1 == 2) { }'         ],
			['<?php function f() { }'        ],
			['<?php function f() { $a[1], }' ],
			// state during parsing (incomplete)
			['<?php if (15 == 12) {',        ['braces'      => 1]],
			['<?php function f() {',         ['braces'      => 1]],
			['<?php $a[',                    ['brackets'    => 1]],
			['<?php if (1 == 2',             ['parentheses' => 1]],
			['<?php namespace N;',           ['namespace' => 'N']],
			['<?php class C {',              ['braces' => 1, 'class' => 'C', 'class_braces' => [0]]],
			['<?php class C implements I {', ['braces' => 1, 'class' => 'C', 'class_braces' => [0]]],
			['<?php namespace N {', ['braces' => 1, 'namespace' => 'N', 'namespace_braces' => 0]],
			['<?php #[A', [
				'attribute' => 'A', 'attribute_brackets' => 0, 'attribute_parentheses' => 0, 'brackets' => 1
			]],
			['<?php #[A([1,2],[3]),', [
				'attribute' => 'A', 'attribute_brackets' => 0, 'attribute_parentheses' => 0, 'brackets' => 1
			]],
			['<?php #[A([1,2],[3]), B', [
				'attribute' => 'B', 'attribute_brackets' => 0, 'attribute_parentheses' => 0, 'brackets' => 1
			]],
			['<?php #[A([1,2],[3]), B([4])', [
				'attribute' => 'B', 'attribute_brackets' => 0, 'attribute_parentheses' => 0, 'brackets' => 1
			]],
			['<?php #[A([1,2],[3]), B([4]', [
				'attribute' => 'B', 'attribute_brackets' => 0, 'attribute_parentheses' => 0,
				'brackets' => 1, 'parentheses' => 1
			]],
			['<?php #[A([1,2],[3]), B([4', [
				'attribute' => 'B', 'attribute_brackets' => 0, 'attribute_parentheses' => 0,
				'brackets' => 2, 'parentheses' => 1
			]],
		];
	}

	//--------------------------------------------------------------------------- provideNamespaceUse
	/** @return array<array{string,array<string,string>}> */
	public static function provideNamespaceUse() : array
	{
		return [
			['<?php use C;',                            ['C' => 'C']],
			['<?php use C as A;',                       ['A' => 'C']],
			['<?php use N\C;',                          ['C' => 'N\C']],
			['<?php use N\C as A;',                     ['A' => 'N\C']],
			['<?php use C, D;',                         ['C' => 'C', 'D' => 'D']],
			['<?php use C as A, D as B;',               ['A' => 'C', 'B' => 'D']],
			['<?php use N\C, N\D;',                     ['C' => 'N\C', 'D' => 'N\D']],
			['<?php use N\C as A, N\D;',                ['A' => 'N\C', 'D' => 'N\D']],
			['<?php use N\C, N\D as B;',                ['C' => 'N\C', 'B' => 'N\D']],
			['<?php use N\C as A, N\D as B;',           ['A' => 'N\C', 'B' => 'N\D']],
			['<?php use N\{C, D};',                     ['C' => 'N\C', 'D' => 'N\D']],
			['<?php use N\{C as A, D};',                ['A' => 'N\C', 'D' => 'N\D']],
			['<?php use N\{C, D as B};',                ['C' => 'N\C', 'B' => 'N\D']],
			['<?php use N\{C as A, D as B};',           ['A' => 'N\C', 'B' => 'N\D']],
			['<?php use N\{C, D}, S\E;',                ['C' => 'N\C', 'D' => 'N\D', 'E' => 'S\E']],
			['<?php use N\{C as A, D as B}, S\E as F;', ['A' => 'N\C', 'B' => 'N\D', 'F' => 'S\E']]
		];
	}

	//------------------------------------------------------------------------------ provideReference
	/** @return array<array{int,array{int,array{int,string,int}},array{string,int,string,int,int}}> */
	public static function provideReference() : array
	{
		return [
			[1, [T_NEW, [T_NAME_FULLY_QUALIFIED, '\A', 1], 2],   ['C', T_NEW, 'A', 1, 2]],
			[2, [T_NEW, [T_NAME_QUALIFIED, 'A\B', 1], 2],        ['C', T_NEW, 'U\N\B', 1, 2]],
			[3, [T_NEW, [T_NAME_QUALIFIED, 'C\D', 1], 2],        ['C', T_NEW, 'N\S\C\D', 1, 2]],
			[4, [T_NEW, [T_NAME_RELATIVE, 'namespace\A', 1], 2], ['C', T_NEW, 'N\S\A', 1, 2]],
			[5, [T_NEW, [T_STRING, 'A', 1], 2],                  ['C', T_NEW, 'U\N', 1, 2]],
			[5, [T_NEW, [T_STRING, 'C', 1], 2],                  ['C', T_NEW, 'N\S\C', 1, 2]],
		];
	}

	//----------------------------------------------------------------------------- provideReferences
	/** @return array<string,array{string,array{string,string,string,int}}> */
	public static function provideReferences() : array
	{
		$counter = 0;
		$data    = explode("\n\n", file_get_contents(__DIR__ . '/scanner.references.provider') ?: '');
		$name    = '';
		$provide = [];
		foreach ($data as $data_key => $buffer) {
			$buffer = "\n" . $buffer . "\n";
			if (preg_match('/\n#\s*-+\s+([\w-]+)\s*\n/', $buffer, $match) > 0) {
				$counter = 0;
				$name    = $match[1];
			}
			$buffer = trim(preg_replace('/\n#[^\[].*\n/', '', $buffer) ?? '');
			if ($buffer === '') {
				unset($data[$data_key]);
				continue;
			}
			[$code, $references] = explode("\n?>\n", $buffer . "\n");
			$references          = trim($references);
			$references          = ($references === '') ? [] : explode("\n", $references);
			foreach ($references as &$reference) {
				$reference = array_map(
					function(string $value) : string { return trim($value); },
					explode(',', $reference)
				);
				if (count($reference) !== 4) {
					trigger_error('scanner.references.provider bad reference ' . join(', ', $reference));
				}
				$reference[3] = intval($reference[3]);
			}
			/** @var array{string,string,string,int} $references */
			/** @phpstan-ignore-next-line bleedingEdge "subtype of native type list<string>" */
			$provide[$name . ':' . ++$counter] = [$code, $references];
		}
		return $provide;
	}

	//------------------------------------------------------------------------------------ testBlocks
	public function testBlocks() : void
	{
		$code = <<<EOT

<?php
class C { }
?>
<html lang="fr">
	new C;
</html>
<?php
new C;
?>
EOT;
		$expected_references = [
			['C', 'declare-class', 'C', 3],
			['',  'new',           'C', 9]
		];

		$scanner = new Scanner;
		$scanner->scan(token_get_all($code));

		foreach ($scanner->references as $key => $reference) {
			$reference = array_slice($reference, 0, 4);
			if (isset(Name::OF[$reference[1]])) {
				$reference[1] = Name::OF[$reference[1]];
			}
			self::assertEquals($expected_references[$key] ?? [], $reference, $this->dataSet());
		}
	}

	//---------------------------------------------------------------------------------- testBrackets
	/** @param array<string,array<int>|int|string> $expected_values */
	#[DataProvider('provideBrackets')]
	public function testBrackets(string $code, array $expected_values = []) : void
	{
		$scanner = new Scanner;
		$scanner->scan(token_get_all($code));
		self::assertComplete($scanner, $expected_values);
	}

	//------------------------------------------------------------------------------ testNamespaceUse
	/** @param array<string,string> $expected_namespace_use */
	#[DataProvider('provideNamespaceUse')]
	public function testNamespaceUse(string $code, array $expected_namespace_use) : void
	{
		$scanner = new Scanner;
		$scanner->scan(token_get_all($code));
		$namespace_use = new ReflectionProperty(Scanner::class, 'namespace_use');
		self::assertEquals(
			$expected_namespace_use,
			$namespace_use->getValue($scanner),
			$this->dataSet()
		);
	}

	//--------------------------------------------------------------------------------- testReference
	/**
	 * @noinspection PhpDocMissingThrowsInspection
	 * @param array{int,array{int,string,int}} $arguments
	 * @param array{string,int,string,int,int} $expected
	 */
	#[DataProvider('provideReference')]
	public function testReference(int $index, array $arguments, array $expected) : void
	{
		$scanner = new Scanner;
		$class   = new ReflectionProperty(Scanner::class, 'class');
		$class->setValue($scanner, 'C');
		$namespace = new ReflectionProperty(Scanner::class, 'namespace');
		$namespace->setValue($scanner, 'N\S');
		$namespace_use = new ReflectionProperty(Scanner::class, 'namespace_use');
		$namespace_use->setValue($scanner, ['A' => 'U\N']);
		$reference = new ReflectionMethod(Scanner::class, 'reference');
		/** @noinspection PhpUnhandledExceptionInspection Will be valid */
		$reference->invokeArgs($scanner, $arguments);
		/** @var array<array{string,int|string,string,int,int}> $references Scanner::$reference */
		$references = (new ReflectionProperty(Scanner::class, 'references'))->getValue($scanner);
		self::assertEquals($expected, $references[0], strval($index));
	}

	//----------------------------------------------------------------- testReferenceInvalidTokenType
	public function testReferenceInvalidTokenType() : void
	{
		$scanner = new Scanner;
		$class   = new ReflectionProperty(Scanner::class, 'class');
		$class->setValue($scanner, 'C');
		$references = new ReflectionProperty(Scanner::class, 'references');
		$references->setValue($scanner, []);
		$reference = new ReflectionMethod(Scanner::class, 'reference');
		/** @noinspection PhpUnhandledExceptionInspection Will be valid */
		$reference->invoke($scanner, T_NEW, [0, 'A', 1], 2);
		self::assertEquals([], $references->getValue($scanner));
	}

	//-------------------------------------------------------------------------------- testReferences
	/** @param array{string,array{string,string,string,string}} $expected_references */
	#[DataProvider('provideReferences')]
	public function testReferences(string $code, array $expected_references) : void
	{
		$scanner = new Scanner;
		$scanner->scan(token_get_all($code));
		$exceptions = [];
		if (str_contains($code, 'namespace N;'))   $exceptions = ['namespace' => 'N'];
		if (str_contains($code, 'namespace A\B;')) $exceptions = ['namespace' => 'A\B'];
		self::assertComplete($scanner, $exceptions);
		$references = $scanner->references;
		foreach ($references as $key => $reference) {
			$reference = array_slice($reference, 0, 4);
			if (isset(Name::OF[$reference[1]])) {
				$reference[1] = Name::OF[$reference[1]];
			}
			self::assertEquals($expected_references[$key] ?? [], $reference, $this->dataSet());
		}
		foreach (array_slice($expected_references, count($references)) as $expected_reference) {
			/** @noinspection PhpUnitMisorderedAssertEqualsArgumentsInspection always false */
			self::assertEquals($expected_reference, [], $this->dataSet());
		}
	}

}
