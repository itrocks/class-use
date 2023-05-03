<?php
namespace ITRocks\Class_Use\Token;

use ITRocks\Class_Use\Index\Cache_Directory;
use ITRocks\Class_Use\Index\Classify;
use ITRocks\Class_Use\Index\Save;
use ITRocks\Class_Use\Index\Scan;
use PHPUnit\Framework\TestCase;

class Classify_Test extends TestCase
{
	use Cache_Directory {
		cacheFileName     as private originalCacheFileName;
		getCacheDirectory as private originalGetCacheDirectory;
	}
	use Classify        { loadAndFilter as private originalLoadAndFilter; }
	use Save;
	use Scan;

	//----------------------------------------------------------------------------------------- $call
	private array $call = [];

	//---------------------------------------------------------------------------------------- $files
	protected array $files;

	//----------------------------------------------------------------------------------------- $mock
	private array $mock = [
		'cacheFileName'     => true,
		'getCacheDirectory' => true
	];

	//----------------------------------------------------------------------------------- $references
	protected array $references;

	//---------------------------------------------------------------------------------------- $reset
	protected bool $reset = false;

	//--------------------------------------------------------------------------------- cacheFileName
	protected function cacheFileName(string $name, int $type = null) : string
	{
		if (!$this->mock[__FUNCTION__]) {
			return $this->originalCacheFileName($name, $type);
		}
		if ($type !== T_FILE) {
			return __DIR__ . '/Load_And_Filter/does-not-exist.json';
		}
		return match($name) {
			'does-not-exist'        => __DIR__ . '/does-not-exist.json',
			'Load_And_Filter/A.php' => __DIR__ . '/A.json',
			'Load_And_Filter/C.php' => __DIR__ . '/C.json',
			'Load_And_Filter/E.php' => __DIR__ . '/E.json',
			default                 => __FILE__
		};
	}

	//----------------------------------------------------------------------------- getCacheDirectory
	protected function getCacheDirectory() : string
	{
		return $this->mock[__FUNCTION__]
			? (__DIR__ . '/Load_And_Filter/cache')
			: $this->originalGetCacheDirectory();
	}

	//----------------------------------------------------------------------------- hasFileReferences
	protected function hasFileReferences(
		string $file_name, int $expected_count = 7, string $message = ''
	) : void
	{
		// ensure all cached arrays have a reference to the file defining the class
		$found_in = [];
		foreach ($this->by as $type => $values1) {
			if ($type === T_FILE) {
				if (isset($values1[$file_name])) {
					$found_in[$type] = true;
				}
				continue;
			}
			foreach ($values1 as $values2) {
				foreach ($values2 as $values3) {
					foreach ($values3 as $values4) {
						foreach (array_keys($values4) as $file_reference) {
							if ($file_reference === $file_name) {
								$found_in[$type] = true;
								continue 5;
							}
						}
					}
				}
			}
		}
		self::assertCount($expected_count, $found_in, $message ?: $file_name);
	}

	//---------------------------------------------------------------------------- hasNoFileReference
	protected function hasNoFileReference(string $file_name) : void
	{
		foreach ($this->by as $type => $values1) {
			if ($type === T_FILE) {
				self::assertArrayNotHasKey($file_name, $values1, "No $file_name");
				continue;
			}
			foreach ($values1 as $values2) {
				foreach ($values2 as $values3) {
					foreach ($values3 as $values4) {
						foreach (array_keys($values4) as $file_reference) {
							self::assertNotEquals($file_name, $file_reference, "No $file_name");
						}
					}
				}
			}
		}
	}

	//--------------------------------------------------------------------------------- loadAndFilter
	public function loadAndFilter(string $file_name) : void
	{
		$this->call[__FUNCTION__] = true;
	}

	//---------------------------------------------------------------------------------- testClassify
	public function testClassify() : void
	{
		$this->call        = [];
		$this->home_length = strlen(__DIR__) + 1;
		$this->references  = [__DIR__ . '/file.php' => [['C', T_NEW, 'D', 10, 100]]];
		$this->classify();
		self::assertEquals(['loadAndFilter' => true], $this->call);
		self::assertEquals(10, $this->by[T_FILE]['file.php'][T_CLASS]['C'][100]          ?? null);
		self::assertEquals(10, $this->by[T_FILE]['file.php'][T_USE]['D'][100]            ?? null);
		self::assertEquals(10, $this->by[T_FILE]['file.php'][T_TYPE][T_NEW][100]         ?? null);
		self::assertEquals(10, $this->by[T_CLASS]['C']['D'][T_NEW]['file.php'][100]      ?? null);
		self::assertEquals(10, $this->by[T_CLASS_TYPE]['C'][T_NEW]['D']['file.php'][100] ?? null);
		self::assertEquals(10, $this->by[T_USE]['D']['C'][T_NEW]['file.php'][100]        ?? null);
		self::assertEquals(10, $this->by[T_USE_TYPE]['D'][T_NEW]['C']['file.php'][100]   ?? null);
		self::assertEquals(10, $this->by[T_TYPE_CLASS][T_NEW]['C']['D']['file.php'][100] ?? null);
		self::assertEquals(10, $this->by[T_TYPE_USE][T_NEW]['D']['C']['file.php'][100]   ?? null);
		/** @noinspection PhpUnitAssertCountInspection COUNT_RECURSIVE */
		self::assertEquals(3 * 3 + 1, count($this->by[T_FILE], COUNT_RECURSIVE));
		unset($this->by[T_FILE]);
		/** @noinspection PhpUnitAssertCountInspection COUNT_RECURSIVE */
		self::assertEquals(6 * 6, count($this->by, COUNT_RECURSIVE));
	}

	//----------------------------------------------------------------------------- testClassifyEmpty
	public function testClassifyEmpty() : void
	{
		$this->references = [];
		$this->classify();
		self::assertEquals([], $this->call);
		/** @noinspection PhpUnitAssertCountInspection COUNT_RECURSIVE */
		self::assertEquals(7, count($this->by, COUNT_RECURSIVE));
	}

	//------------------------------------------------------------------------------------ testFilter
	public function testFilter() : void
	{
		$this->home_length = strlen(__DIR__ . '/Load_And_Filter/');
		$this->reset       = true;
		$this->scanner     = new Scanner;
		$this->vendor      = false;
		$this->scanDirectory(__DIR__ . '/Load_And_Filter');
		$this->classify();
		$by = $this->by;
		foreach (['A', 'C', 'E', 'F'] as $class) {
			$this->by = $by;
			$this->hasFileReferences("$class.php");
			$this->originalLoadAndFilter("$class.php");
			$this->hasNoFileReference("$class.php");
		}
	}

	//-------------------------------------------------------------------------------------- testLoad
	public function testLoad() : void
	{
		$this->mock['cacheFileName'] = false;
		/** @noinspection PhpUnhandledExceptionInspection No Exception here */
		$this->setHome(__DIR__ . '/Load_And_Filter');
		if (is_dir(__DIR__ . '/Load_And_Filter/cache')) {
			exec('rm -rf "' . __DIR__ . '/Load_And_Filter/cache"');
		}
		mkdir(__DIR__ . '/Load_And_Filter/cache');
		$this->pretty     = true;
		$this->reset      = true;
		$this->scanner    = new Scanner;
		$this->start_time = time();
		$this->vendor     = false;
		$this->scanDirectory(__DIR__ . '/Load_And_Filter');
		$this->classify();
		$this->save();
		// TODO Proof of expected count values
		$combinations = [
			'A' => ['C' => 4, 'E' => 2, 'F' => 2],
			'C' => ['A' => 4, 'E' => 4, 'F' => 2],
			'E' => ['A' => 2, 'C' => 4, 'F' => 2],
			'F' => ['A' => 2, 'C' => 2, 'E' => 2]
		];
		foreach ($combinations as $not_class => $classes) {
			$this->by = [];
			$this->originalLoadAndFilter("$not_class.php");
			$this->hasNoFileReference("$not_class.php");
			foreach ($classes as $class => $expected_count) {
				if ($class === $not_class) {
					continue;
				}
				$this->hasFileReferences("$class.php", $expected_count, "Not $not_class + Class $class");
			}
		}
		exec('rm -rf "' . __DIR__ . '/Load_And_Filter/cache"');
		$this->mock['cacheFileName'] = true;
	}

	//--------------------------------------------------------------------- testLoadAndFilterNotExist
	public function testLoadAndFilterNotExist() : void
	{
		$this->references = [];
		$this->classify();
		$this->originalLoadAndFilter('does-not-exist');
		/** @noinspection PhpUnitAssertCountInspection COUNT_RECURSIVE */
		self::assertEquals(7, count($this->by, COUNT_RECURSIVE));
	}

}
