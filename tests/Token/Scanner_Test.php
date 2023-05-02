<?php
namespace ITRocks\Class_Use\Token;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
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

	//------------------------------------------------------------------------------------ REFERENCES
	protected const REFERENCES = [T_CLASS, T_USE, T_TYPE, T_LINE];

	//-------------------------------------------------------------------------------- assertComplete
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

	//----------------------------------------------------------------------------- provideReferences
	public static function provideReferences() : array
	{
		$counter = 0;
		$data    = explode("\n\n", file_get_contents(__DIR__ . '/scanner.references.provider'));
		$name    = '';
		$provide = [];
		foreach ($data as $data_key => $buffer) {
			$buffer = "\n" . $buffer . "\n";
			if (preg_match('/\n#\s*-+\s+([\w-]+)\s*\n/', $buffer, $match)) {
				$counter = 0;
				$name    = $match[1];
			}
			$buffer = trim(preg_replace('/\n#[^\[].*\n/', '', $buffer));
			if ($buffer === '') {
				unset($data[$data_key]);
				continue;
			}
			[$code, $references] = explode("\n?>\n", $buffer . "\n");
			$references          = trim($references);
			$references          = ($references === '') ? [] : explode("\n", $references);
			foreach ($references as &$reference) {
				$reference = array_map(
					function(string $value) { return trim($value); },
					explode(',', $reference)
				);
				$reference[3] = intval($reference[3]);
			}
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
<html>
	$o = new C;
</html>
<?php
$o = new C;
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
			$this->assertEquals($expected_references[$key] ?? [], $reference, $this->dataSet());
		}
	}

	//---------------------------------------------------------------------------------- testBrackets
	#[DataProvider('provideBrackets')]
	public function testBrackets(string $code, array $expected_values = []) : void
	{
		$scanner = new Scanner;
		$scanner->scan(token_get_all($code));
		$this->assertComplete($scanner, $expected_values);
	}

	//------------------------------------------------------------------------------ testNamespaceUse
	#[DataProvider('provideNamespaceUse')]
	public function testNamespaceUse(string $code, array $expected_namespace_use) : void
	{
		$scanner = new Scanner;
		$scanner->scan(token_get_all($code));
		$namespace_use = new ReflectionProperty(Scanner::class, 'namespace_use');
		$this->assertEquals(
			$expected_namespace_use,
			$namespace_use->getValue($scanner),
			$this->dataSet()
		);
	}

	//-------------------------------------------------------------------------------- testReferences
	#[DataProvider('provideReferences')]
	public function testReferences(string $code, array $expected_references) : void
	{
		$scanner = new Scanner;
		$scanner->scan(token_get_all($code));
		$exceptions = [];
		if (str_contains($code, 'namespace N;'))   $exceptions = ['namespace' => 'N'];
		if (str_contains($code, 'namespace A\B;')) $exceptions = ['namespace' => 'A\B'];
		$this->assertComplete($scanner, $exceptions);
		$references = $scanner->references;
		foreach ($references as $key => $reference) {
			$reference = array_slice($reference, 0, 4);
			if (isset(Name::OF[$reference[1]])) {
				$reference[1] = Name::OF[$reference[1]];
			}
			$this->assertEquals($expected_references[$key] ?? [], $reference, $this->dataSet());
		}
		foreach (array_slice($expected_references, count($references)) as $expected_reference) {
			/** @noinspection PhpUnitMisorderedAssertEqualsArgumentsInspection always false */
			$this->assertEquals($expected_reference, [], $this->dataSet());
		}
	}

}
