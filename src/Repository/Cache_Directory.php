<?php
namespace ITRocks\Depend\Repository;

use Exception;

trait Cache_Directory
{

	//------------------------------------------------------------------------------- CACHE_DIRECTORY
	const CACHE_DIRECTORY = '/cache/depend';

	//----------------------------------------------------------------------------------------- $home
	/** Home directory, without right '/' */
	protected string $home;

	//---------------------------------------------------------------------------------- $home_length
	protected int $home_length;

	//--------------------------------------------------------------------------------------- $vendor
	protected bool $vendor;

	//--------------------------------------------------------------------------------- cacheFileName
	protected function cacheFileName(string $name, string $type = '') : string
	{
		$directory = $this->getCacheDirectory();
		$file_name = str_replace(['/', '\\'], '-', $name);
		$file_name = (str_ends_with($file_name, '.php') ? substr($file_name, 0, -4) : $file_name)
			. '.json';
		return ($type === '') ? "$directory/$file_name" : "$directory/$type/$file_name";
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
		$directory = '';
		foreach (array_slice(explode('/', $this->getCacheDirectory()), 1) as $subdirectory) {
			$directory .= '/' . $subdirectory;
			if (!is_dir($directory)) {
				mkdir($directory);
			}
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
	protected function setHome(string $home) : void
	{
		if ($home !== '') {
			$this->home        = realpath($home);
			$this->home_length = strlen($this->home);
			return;
		}
		$home = str_replace('\\', '/', getcwd());
		while (
			str_contains($home, '/')
			&& !file_exists("$home/composer.json")
			&& !file_exists("$home/composer.lock")
		) {
			$home = substr($home, 0, strrpos($home, '/'));
		}
		$this->home        = $home;
		$this->home_length = strlen($this->home);
	}

}
