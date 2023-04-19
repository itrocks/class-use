<?php
namespace ITRocks\Depend\Repository;

trait Cache_Directory
{

	//----------------------------------------------------------------------------------------- $home
	/** Home directory, without right '/' */
	protected string $home;

	//--------------------------------------------------------------------------------------- $vendor
	protected bool $vendor;

	//-------------------------------------------------------------------------------- cacheDirectory
	protected function cacheDirectory() : string
	{
		return $this->home . '/cache/dependencies';
	}

	//--------------------------------------------------------------------------------- cacheFileName
	protected function cacheFileName(string $name, string $type = '') : string
	{
		$directory = $this->cacheDirectory();
		$file_name = str_replace(['/', '\\'], '-', $name);
		$file_name = (str_ends_with($file_name, '.php') ? substr($file_name, 0, -4) : $file_name)
			. '.json';
		return ($type === '') ? "$directory/$file_name" : "$directory/$type/$file_name";
	}

	//--------------------------------------------------------------------------------------- getHome
	public function getHome() : string
	{
		return $this->home;
	}

	//------------------------------------------------------------------------------------- getTarget
	public function getTarget() : string
	{
		return $this->home . '/cache/dependencies';
	}

	//----------------------------------------------------------------------------------- prepareHome
	protected function prepareHome() : void
	{
		$home = $this->home;
		if (!is_dir($home)) {
			if ($home !== '') {
				$home = ' ' . $home;
			}
			echo "Missing directory$home: need a root PHP project directory with a composer.lock file\n";
			exit(1);
		}
		if (!is_dir("$home/cache")) {
			mkdir("$home/cache");
		}
		if (
			$this->reset
			&& !str_contains('"', $home)
			&& is_dir("$home/cache/dependencies")
		) {
			exec('rm -rf "' . $home . '/cache/dependencies"');
			clearstatcache(true);
		}
		if (!is_dir("$home/cache"))              mkdir("$home/cache");
		if (!is_dir("$home/cache/dependencies")) mkdir("$home/cache/dependencies");
	}

	//--------------------------------------------------------------------------------------- setHome
	protected function setHome(string $home) : void
	{
		$this->home = $home;
		if ($home !== '') {
			return;
		}
		$home = str_replace('\\', '/', getcwd());
		while (
			str_contains($home, '/')
			&& !(file_exists("$home/cache") && file_exists("$home/composer.json"))
			&& !file_exists("$home/composer.lock")
		) {
			$home = substr($home, 0, strrpos($home, '/'));
		}
		$this->home = $home;
	}

}
