<?php
namespace ITRocks\Class_Use\Index;

use PHPUnit\Framework\TestCase;

class Save_Test extends TestCase
{
	use Cache_Directory { getCacheDirectory as private originGetCacheDirectory; }
	use Save;
	
	//------------------------------------------------------------------------------------------- $by
	protected array $by;

	//---------------------------------------------------------------------------------------- $files
	protected array $files;

	//----------------------------------------------------------------------------- getCacheDirectory
	public function getCacheDirectory() : string
	{
		return __DIR__ . '/cache';
	}

	//-------------------------------------------------------------------------------- recurseScanDir
	/** @return string[] */
	private function recurseScanDir(string $directory, string $parent = '') : array
	{
		$files = [];
		foreach (scandir($directory) as $file) {
			if (str_starts_with($file, '.')) {
				continue;
			}
			if (is_dir("$directory/$file")) {
				$files = array_merge($files, $this->recurseScanDir("$directory/$file", "$parent/$file"));
			}
			else {
				$files[] = "$parent/$file";
			}
		}
		return $files;
	}

	//-------------------------------------------------------------------------------------- testSave
	public function testSave() : void
	{
		$cache_directory = $this->getCacheDirectory();
		if (!is_dir($cache_directory)) {
			mkdir($cache_directory);
		}
		$this->by = [
			T_FILE => ['C.php' => [
				T_CLASS => ['C' => [3 => 2]],
				T_TYPE  => [T_DECLARE_CLASS => [3 => 2], T_NEW => [10 => 3]],
				T_USE   => ['C' => [3 => 2, 10 => 3]]
			]],
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
		$this->files      = ['C.php'];
		$this->pretty     = false;
		$this->start_time = time();
		$this->save();
		$actual = [];
		foreach ($this->recurseScanDir($cache_directory) as $file) {
			$actual[$file] = file_get_contents($cache_directory . $file);
		}
		ksort($actual);
		$expected = [
			'/class-type/C.json'   => '{"993":{"C":{"C.php":{"3":2}}}}',
			'/class/C.json'        => '{"C":{"993":{"C.php":{"3":2}}}}',
			'/file/C.json'         =>
				'{"333":{"C":{"3":2}},"983":{"993":{"3":2},"284":{"10":3}},"318":{"C":{"3":2,"10":3}}}',
			'/files.json'          => '["C.php"]',
			'/type-class/284.json' => '{"":{"C":{"C.php":{"10":3}}}}',
			'/type-class/993.json' => '{"C":{"C":{"C.php":{"3":2}}}}',
			'/type-use/284.json'   => '{"C":{"":{"C.php":{"10":3}}}}',
			'/type-use/993.json'   => '{"C":{"C":{"C.php":{"3":2}}}}',
			'/use-type/C.json'     => '{"993":{"C":{"C.php":{"3":2}}},"284":{"":{"C.php":{"10":3}}}}',
			'/use/C.json'          => '{"C":{"993":{"C.php":{"3":2}}},"":{"284":{"C.php":{"10":3}}}}'
		];
		exec('rm -rf "' . $cache_directory . '"');
		self::assertEquals($expected, $actual);
	}

}