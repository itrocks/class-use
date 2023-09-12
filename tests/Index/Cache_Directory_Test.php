<?php
namespace ITRocks\Class_Use\Index;

use Exception;
use PHPUnit\Framework\TestCase;

class Cache_Directory_Test extends TestCase
{
	use Cache_Directory {
		getCacheDirectory as private originalGetCacheDirectory;
		purgeCache        as private originalPurgeCache;
	}

	//----------------------------------------------------------------------------------------- $call
	/** @var array<string,bool> */
	private array $call = [];

	//----------------------------------------------------------------------------------------- $mock
	/** @var array<string,bool> */
	private array $mock = [
		'getCacheDirectory' => true,
		'purgeCache'        => true
	];

	//---------------------------------------------------------------------------------------- $reset
	protected bool $reset = false;

	//----------------------------------------------------------------------------- getCacheDirectory
	public function getCacheDirectory() : string
	{
		return $this->mock[__FUNCTION__]
			? '/cache/directory'
			: $this->originalGetCacheDirectory();
	}

	//------------------------------------------------------------------------------------ purgeCache
	public function purgeCache() : void
	{
		$this->mock[__FUNCTION__]
			? $this->call[__FUNCTION__] = true
			: $this->originalPurgeCache();
	}

	//----------------------------------------------------------------------------------------- setUp
	public function setUp() : void
	{
		$this->home = '/home/vendor/project';
	}

	//------------------------------------------------------------------ testCacheFileNameWindowsPath
	public function testCacheFileNameWindowsPath() : void
	{
		$actual   = $this->cacheFileName('vendor\\project\\path\\File_Name.php');
		$expected = '/cache/directory/vendor-project-path-File_Name.json';
		self::assertEquals($expected, $actual);
	}

	//--------------------------------------------------------------------- testCacheFileNameWithType
	public function testCacheFileNameWithType() : void
	{
		$actual   = $this->cacheFileName('vendor/project/path/File_Name.php', T_CLASS_TYPE);
		$expected = '/cache/directory/class-type/vendor-project-path-File_Name.json';
		self::assertEquals($expected, $actual);
	}

	//------------------------------------------------------------------ testCacheFileNameWithoutType
	public function testCacheFileNameWithoutType() : void
	{
		$actual   = $this->cacheFileName('vendor/project/path/File_Name.php');
		$expected = '/cache/directory/vendor-project-path-File_Name.json';
		self::assertEquals($expected, $actual);
	}

	//------------------------------------------------------------------------- testGetCacheDirectory
	public function testGetCacheDirectory() : void
	{
		$this->mock['getCacheDirectory'] = false;
		$actual   = $this->getCacheDirectory();
		$expected = '/home/vendor/project/cache/class-use';
		self::assertEquals($expected, $actual);
		$this->mock['getCacheDirectory'] = true;
	}

	//----------------------------------------------------------------------------------- testGetHome
	public function testGetHome() : void
	{
		$actual   = $this->getHome();
		$expected = '/home/vendor/project';
		self::assertEquals($expected, $actual);
	}

	//------------------------------------------------------------------------------- testPrepareHome
	public function testPrepareHome() : void
	{
		$back_home   = $this->home;
		$this->call  = [];
		$this->home  = __DIR__;
		$this->reset = true;
		$this->mock['getCacheDirectory'] = false;
		$this->mock['purgeCache']        = false;

		/** @noinspection PhpUnhandledExceptionInspection Must not throw exception */
		$this->prepareHome();
		self::assertDirectoryExists(__DIR__ . '/cache/class-use');

		$this->originalPurgeCache();
		rmdir(__DIR__ . '/cache');
		$this->mock['getCacheDirectory'] = true;
		$this->mock['purgeCache']        = true;
		$this->home  = $back_home;
		$this->reset = false;
	}

	//-------------------------------------------------------------------------- testPrepareHomeEmpty
	public function testPrepareHomeEmpty() : void
	{
		$back_home  = $this->home;
		$this->call = [];
		$this->home = '';
		$this->expectException(Exception::class);
		$this->expectExceptionMessage(
			'Missing directory: need a root PHP project directory with a composer.lock file'
		);
		$this->prepareHome();
		self::assertNull($this->call['purgeCache'] ?? null);
		$this->home = $back_home;
	}
	
	//----------------------------------------------------------------------- testPrepareHomeNotExist
	public function testPrepareHomeNotExist() : void
	{
		$back_home = $this->home;
		do {
			$this->home = '/home/' . uniqid();
		} while (is_dir($this->home));
		$this->call = [];
		$this->expectException(Exception::class);
		$this->expectExceptionMessage(
			"Missing directory $this->home: need a root PHP project directory with a composer.lock file"
		);
		$this->prepareHome();
		self::assertNull($this->call['purgeCache'] ?? null);
		$this->home = $back_home;
	}

	//-------------------------------------------------------------------------- testPrepareHomeReset
	public function testPrepareHomeReset() : void
	{
		$back_home   = $this->home;
		$this->call  = [];
		$this->home  = __DIR__;
		$this->reset = true;
		$this->mock['getCacheDirectory'] = false;

		/** @noinspection PhpUnhandledExceptionInspection Must not throw exception */
		$this->prepareHome();
		self::assertTrue($this->call['purgeCache'] ?? null);

		$this->originalPurgeCache();
		rmdir(__DIR__ . '/cache');
		$this->mock['getCacheDirectory'] = true;
		$this->home  = $back_home;
		$this->reset = false;
	}

	//----------------------------------------------------------------------------------- testSetHome
	public function testSetHome() : void
	{
		$back_home = $this->home;
		/** @noinspection PhpUnhandledExceptionInspection Must not throw exception */
		$this->setHome(__DIR__);
		self::assertEquals(__DIR__, $this->home);
		self::assertEquals(strlen(__DIR__) + 1, $this->home_length);
		$this->home = $back_home;
	}

	//------------------------------------------------------------------------------- testSetHomeAuto
	public function testSetHomeAuto() : void
	{
		$back_home = $this->home;
		$cwd       = getcwd();
		if ($cwd === false) {
			$cwd = '.';
		}
		chdir(__DIR__);
		/** @noinspection PhpUnhandledExceptionInspection Must not throw exception */
		$this->setHome();
		$expected = realpath(__DIR__ . '/../..');
		$expected = ($expected === false) ? '-' : $expected;
		self::assertEquals($expected, $this->home);
		self::assertEquals(strlen($expected) + 1, $this->home_length);
		chdir($cwd);
		$this->home = $back_home;
	}

	//--------------------------------------------------------------------------- testSetHomeNotExist
	public function testSetHomeNotExist() : void
	{
		$back_home = $this->home;
		do {
			$home = '/home/' . uniqid();
		} while (is_dir($home));
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("Directory $home does not exist");
		$this->setHome($home);
		$this->home = $back_home;
	}

}
