<?php
namespace ITRocks\Class_Use\Repository;

use ITRocks\Class_Use\Tokens_Scanner;

trait Scanner
{

	//------------------------------------------------------------------------------- $tokens_scanner
	public Tokens_Scanner $tokens_scanner;

	//--------------------------------------------------------------------------------- scanDirectory
	public function scanDirectory(string $directory = '', int $depth = 0) : void
	{
		if ($directory === '') {
			$directory = $this->home;
		}
		$this->directories_count ++;
		foreach (scandir($directory) as $file) {
			if (str_starts_with($file, '.')) {
				continue;
			}
			$file = "$directory/$file";
			if (is_dir($file) && ($depth || $this->vendor || !str_ends_with($file, '/vendor'))) {
				$this->scanDirectory($file, $depth + 1);
			}
			elseif (str_ends_with($file, '.php')) {
				$home_length   = $this->home_length;
				$this->files[] = substr($file, $home_length);
				if (
					$this->reset
					|| !file_exists($cache_file = $this->cacheFileName(substr($file, $home_length), 'file'))
					|| (filemtime($file) > filemtime($cache_file))
					|| str_contains($file, 'repository/Repository')
				) {
					$this->scanFile($file);
				}
			}
		}
	}

	//-------------------------------------------------------------------------------------- scanFile
	public function scanFile(string $file) : void
	{
		$this->files_count ++;
		if (isset($this->file_tokens)) {
			$tokens = $this->file_tokens[substr($file, $this->home_length)] ?? null;
			if (!isset($tokens)) {
				$tokens = token_get_all(file_get_contents($file));
				$this->file_tokens[substr($file, $this->home_length)] = $tokens;
			}
		}
		else {
			$tokens = token_get_all(file_get_contents($file));
		}
		$this->tokens_scanner->scan($tokens);
		$this->references[$file] = $this->tokens_scanner->references;
		$this->references_count += count($this->tokens_scanner->references);
	}

}
