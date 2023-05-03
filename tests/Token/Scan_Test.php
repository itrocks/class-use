<?php
namespace ITRocks\Class_Use\Token;

use ITRocks\Class_Use\Index\Cache_Directory;
use ITRocks\Class_Use\Index\Scan;
use PHPUnit\Framework\TestCase;

class Scan_Test extends TestCase
{
	use Cache_Directory {
		cacheFileName as private originalCacheFileName;
	}
	use Scan {
		scanDirectory as private originalScanDirectory;
		scanFile      as private originalScanFile;
	}

	//----------------------------------------------------------------------------------------- $call
	private array $call = [];

	//---------------------------------------------------------------------------------------- $files
	protected array $files;

	//----------------------------------------------------------------------------------------- $mock
	private array $mock = [
		'scanDirectory' => true,
		'scanFile'      => true
	];

	//----------------------------------------------------------------------------------- $references
	protected array $references;

	//---------------------------------------------------------------------------------------- $reset
	protected bool $reset = false;

	//--------------------------------------------------------------------------------- cacheFileName
	protected function cacheFileName(string $name, int $type = null) : string
	{
		if ($type !== T_FILE) {
			return '';
		}
		if (str_contains($name, 'cached')) {
			return __DIR__ . '/directory/cache-sub-cached.json';
		}
		if (str_contains($name, 'sub/file.php')) {
			return __DIR__ . '/directory/cache-sub-file.json';
		}
		return __DIR__ . '/directory/cache-no-file.json';
	}

	//--------------------------------------------------------------------------------- scanDirectory
	public function scanDirectory(string $directory = '', int $depth = 0) : void
	{
		$this->mock[__FUNCTION__]
			? $this->call[__FUNCTION__][$directory] = true
			: $this->originalScanDirectory($directory, $depth);
	}

	//-------------------------------------------------------------------------------------- scanFile
	public function scanFile(string $file) : void
	{
		$this->mock[__FUNCTION__]
			? $this->call[__FUNCTION__][$file] = true
			: $this->originalScanFile($file);
	}

	//----------------------------------------------------------------------------------------- setUp
	public function setUp() : void
	{
		$this->scanner = new class extends Scanner {
			public function scan(array $tokens) : void
			{
				$this->references = ['ok'];
			}
		};
		/** @noinspection PhpUnhandledExceptionInspection Should not throw exception */
		$this->setHome(__DIR__);
	}

	//---------------------------------------------------------------------------- testKeepFileTokens
	public function testKeepFileTokens() : void
	{
		$this->keepFileTokens();
		self::assertEquals([], $this->file_tokens);
		unset($this->file_tokens);
	}

	//--------------------------------------------------------------------- testKeepFileTokensAlready
	public function testKeepFileTokensAlready() : void
	{
		$this->file_tokens = [__FILE__ => 'ok'];
		$this->keepFileTokens();
		self::assertEquals([__FILE__ => 'ok'], $this->file_tokens);
		unset($this->file_tokens);
	}

	//----------------------------------------------------------------------------- testScanDirectory
	/**
	 * Multiple tests of scanDirectory :
	 * - default home directory
	 * - ignore .files
	 * - ignore non .php files
	 * - ignore files already in cache
	 * - do not ignore files already in cache but older than the original file
	 * - scan subdirectories
	 * - result files
	 * - calls to scanFile
	 */
	public function testScanDirectory() : void
	{
		if (!is_dir(__DIR__ . '/directory')) {
			mkdir(__DIR__ . '/directory');
		}
		if (!is_dir(__DIR__ . '/directory/sub')) {
			mkdir(__DIR__ . '/directory/sub');
		}
		touch(__DIR__ . '/directory/.ignore');
		touch(__DIR__ . '/directory/file.php');
		touch(__DIR__ . '/directory/ignore.xml');
		touch(__DIR__ . '/directory/sub/file.php');
		touch(__DIR__ . '/directory/sub/cached.php');
		touch(__DIR__ . '/directory/cache-sub-cached.json');
		touch(__DIR__ . '/directory/cache-sub-file.json', time() - 1);
		$this->vendor = false;
		/** @noinspection PhpUnhandledExceptionInspection Should not throw exception */
		$this->setHome(__DIR__ . '/directory');

		$this->call = [];
		$this->directories_count = 0;
		$this->mock['scanDirectory'] = false;
		$this->scanDirectory();
		$this->mock['scanDirectory'] = true;

		unlink(__DIR__ . '/directory/cache-sub-cached.json');
		unlink(__DIR__ . '/directory/cache-sub-file.json');
		unlink(__DIR__ . '/directory/.ignore');
		unlink(__DIR__ . '/directory/file.php');
		unlink(__DIR__ . '/directory/ignore.xml');
		unlink(__DIR__ . '/directory/sub/cached.php');
		unlink(__DIR__ . '/directory/sub/file.php');
		rmdir(__DIR__ . '/directory/sub');
		rmdir(__DIR__ . '/directory');
		/** @noinspection PhpUnhandledExceptionInspection Should not throw exception */
		$this->setHome(__DIR__);

		self::assertEquals(2, $this->directories_count, 'directories_count');

		$expected = [
			__DIR__ . '/directory/file.php'     => 'ok',
			__DIR__ . '/directory/sub/file.php' => 'ok'
		];
		ksort($this->call['scanFile']);
		self::assertEquals($expected, $this->call['scanFile'], 'scanFile');
		$expected = ['file.php', 'sub/cached.php', 'sub/file.php'];
		self::assertEquals($expected, $this->files, 'files');
	}

	//---------------------------------------------------------------------------------- testScanFile
	public function testScanFile() : void
	{
		$this->mock['scanFile'] = false;
		$this->scanFile(__FILE__);
		$this->mock['scanFile'] = true;
		$expected = ['ok'];
		self::assertEquals($expected, $this->references[__FILE__] ?? []);
	}

	//----------------------------------------------------------------------------- testScanFileCache
	public function testScanFileCache() : void
	{
		$this->files_count = 0;
		$this->keepFileTokens();
		$this->mock['scanFile'] = false;
		$this->scanFile(__FILE__);
		$this->mock['scanFile'] = true;
		self::assertEquals(1, $this->files_count);
		self::assertIsArray($this->file_tokens[substr(__FILE__, strlen(__DIR__) + 1)] ?? null);
		unset($this->file_tokens);
	}

}
