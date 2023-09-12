<?php
namespace ITRocks\Class_Use\Index;

trait Test_Common
{

	//-------------------------------------------------------------------------------- recurseScanDir
	/** @return list<string> */
	private function recurseScanDir(string $directory, string $parent = '') : array
	{
		$files     = [];
		$dir_files = scandir($directory);
		foreach ((($dir_files === false) ? [] : $dir_files) as $file) {
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

}
