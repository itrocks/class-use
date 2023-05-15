<?php
namespace ITRocks\Class_Use\Index;

use Exception;
use ITRocks\Class_Use\Token\Name;

trait Cache_Directory
{

	//------------------------------------------------------------------------------- CACHE_DIRECTORY
	const CACHE_DIRECTORY = '/cache/class-use';

	//----------------------------------------------------------------------------------------- $home
	/** Home directory, without right '/' */
	protected string $home = '';

	//---------------------------------------------------------------------------------- $home_length
	protected int $home_length = 0;

	//--------------------------------------------------------------------------------------- $vendor
	protected bool $vendor = false;

	//--------------------------------------------------------------------------------- cacheFileName
	/**
	 * @param int|string $name The name of the php file, relative to the home directory
	 * @param int|null   $type The type of the cache file
	 * @return string The json cache filename, absolute
	 */
	protected function cacheFileName(int|string $name, int $type = null) : string
	{
		$directory = $this->getCacheDirectory();
		$file_name = str_replace(['/', '\\'], '-', strval($name));
		$file_name = (str_ends_with($file_name, '.php') ? substr($file_name, 0, -4) : $file_name)
			. '.json';
		return isset($type)
			? ($directory . '/' . Name::OF[$type] . '/' . $file_name)
			: ($directory . '/' . $file_name);
	}

	//----------------------------------------------------------------------------- getCacheDirectory
	public function getCacheDirectory() : string
	{
		return $this->home . static::CACHE_DIRECTORY;
	}

	//--------------------------------------------------------------------------------------- getHome
	public function getHome() : string
	{
		return $this->home;
	}

	//----------------------------------------------------------------------------------- prepareHome
	protected function prepareHome() : void
	{
		$home = $this->home;
		if (!is_dir($home)) {
			if ($home !== '') {
				$home = ' ' . $home;
			}
			/** @noinspection PhpUnhandledExceptionInspection app-level */
			throw new Exception(
				"Missing directory$home: need a root PHP project directory with a composer.lock file"
			);
		}
		if ($this->reset) {
			$this->purgeCache();
		}
		$cache_directory = $this->getCacheDirectory();
		if (!is_dir($cache_directory)) {
			mkdir($cache_directory, 0777, true);
		}
	}

	//------------------------------------------------------------------------------------ purgeCache
	public function purgeCache() : void
	{
		$home = $this->home;
		if (str_contains('"', $home) || !is_dir($this->getCacheDirectory())) {
			return;
		}
		exec('rm -rf "' . $this->getCacheDirectory() . '"');
		clearstatcache(true);
	}

	//--------------------------------------------------------------------------------------- setHome
	/** @throws Exception */
	protected function setHome(string $home = '') : void
	{
		if ($home !== '') {
			if (!is_dir($home)) {
				throw new Exception("Directory $home does not exist");
			}
			$this->home        = realpath($home) ?: '';
			$this->home_length = strlen($this->home) + (($this->home === DIRECTORY_SEPARATOR) ? 0 : 1);
			return;
		}
		$directory = $home = str_replace('\\', '/', getcwd() ?: '.');
		while (
			(($slash = strrpos($home, '/')) !== false)
			&& !file_exists("$home/composer.json")
			&& !file_exists("$home/composer.lock")
		) {
			$home = substr($home, 0, $slash);
		}
		if ($home === '') {
			throw new Exception("Directory $directory does not contain a php project");
		}
		$this->home        = $home;
		$this->home_length = strlen($this->home) + 1;
	}

}
