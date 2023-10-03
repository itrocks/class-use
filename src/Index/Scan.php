<?php
namespace ITRocks\Class_Use\Index;

use ITRocks\Class_Use\Token;

trait Scan
{

	//---------------------------------------------------------------------------- $directories_count
	public int $directories_count = 0;

	//---------------------------------------------------------------------------------- $file_tokens
	/**
	 * File tokens will be kept during scan only if this was initialized using keepFileTokens().
	 *
	 * @var array<string,array<int,array{int,string,int}|string>>
	 * <string $file_path_relative_to_project, $tokens <
	 *   int $token_key,
	 *   array $tokens{int $token_index, string $content, int $line} | string $character
	 * >>
	 */
	public ?array $file_tokens = null;

	//---------------------------------------------------------------------------------- $files_count
	public int $files_count = 0;

	//----------------------------------------------------------------------------- $references_count
	public int $references_count = 0;

	//-------------------------------------------------------------------------------------- $scanner
	public Token\Scanner $scanner;

	//-------------------------------------------------------------------------------- keepFileTokens
	/** Keep file tokens in memory : this allows other libraries to avoid re-scanning file tokens */
	public function keepFileTokens() : void
	{
		if (is_null($this->file_tokens)) {
			$this->file_tokens = [];
		}
	}

	//--------------------------------------------------------------------------------- scanDirectory
	/** @param string $directory Absolute path to the directory */
	public function scanDirectory(string $directory = '', int $depth = 0) : void
	{
		if ($directory === '') {
			$directory = $this->home;
		}
		$this->directories_count ++;
		$files = scandir($directory);
		foreach ((($files === false) ? [] : $files) as $file) {
			if (str_starts_with($file, '.')) {
				continue;
			}
			$file = "$directory/$file";
			if (is_dir($file)) {
				if (
					($depth > 0)
					|| (
						!str_ends_with($file, '/cache')
						&& ($this->vendor || !str_ends_with($file, '/vendor'))
					)
				) {
					$this->scanDirectory($file, $depth + 1);
				}
			}
			elseif (str_ends_with($file, '.php')) {
				$home_length   = $this->home_length;
				$this->files[] = substr($file, $home_length);
				if (
					$this->reset
					|| !file_exists($cache_file = $this->cacheFileName(substr($file, $home_length), T_FILE))
					|| (filemtime($file) > filemtime($cache_file))
				) {
					$this->scanFile($file);
				}
			}
		}
	}

	//-------------------------------------------------------------------------------------- scanFile
	/** @param string $file Absolute path of the file */
	public function scanFile(string $file) : void
	{
		$file_relative = substr($file, $this->home_length);
		$this->files_count ++;
		if (isset($this->file_tokens)) {
			$tokens = $this->file_tokens[$file_relative] ?? null;
			if (is_null($tokens)) {
				$file_content = file_get_contents($file);
				$tokens       = token_get_all(($file_content === false) ? '' : $file_content);
				$this->file_tokens[$file_relative] = $tokens;
			}
		}
		else {
			$file_content = file_get_contents($file);
			$tokens       = token_get_all(($file_content === false) ? '' : $file_content);
		}
		$this->scanner->scan($tokens);
		$this->references[$file_relative] = $this->scanner->references;
		$this->references_count          += count($this->scanner->references);
	}

}
