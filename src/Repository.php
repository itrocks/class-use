<?php
namespace ITRocks\Class_Use;

use ITRocks\Class_Use\Repository\Cache_Directory;
use ITRocks\Class_Use\Repository\Classify;
use ITRocks\Class_Use\Repository\Counters;
use ITRocks\Class_Use\Repository\Save;
use ITRocks\Class_Use\Repository\Scanner;
use ITRocks\Class_Use\Repository\Search;

class Repository
{
	use Cache_Directory, Classify, Counters, Save, Scanner, Search;

	//----------------------------------------------------------------------------------------- FLAGS
	public const RESET  = 2;
	public const VENDOR = 1;

	//---------------------------------------------------------------------------------- $file_tokens
	/**
	 * File tokens will be kept during scan only if this was initialized using keepFileTokens().
	 * [string $file_path_relative_to_project => array $file_tokens]
	 */
	public array $file_tokens;

	//---------------------------------------------------------------------------------------- $files
	/** @var string[] string $file_name[int] */
	protected array $files = [];

	//--------------------------------------------------------------------------------------- $pretty
	protected bool $pretty;

	//----------------------------------------------------------------------------------- $references
	/** @var (int|string)[][] [string $file][string $class, string $use, string $type, int $line] */
	protected array $references = [];

	//---------------------------------------------------------------------------------------- $reset
	protected bool $reset;

	//------------------------------------------------------------------------------------ $singleton
	private static self $singleton;

	//----------------------------------------------------------------------------------- __construct
	public function __construct(int $flags = 0, string $home = '')
	{
		$this->pretty     = $flags & self::PRETTY;
		$this->reset      = $flags & self::RESET;
		$this->start_time = time();
		$this->vendor     = $flags & self::VENDOR;
		$this->setHome($home);
		$this->prepareHome();
	}

	//------------------------------------------------------------------------------------------- get
	public static function get() : static
	{
		return static::$singleton ?? (static::$singleton = new static);
	}

	//-------------------------------------------------------------------------------- keepFileTokens
	/** Keep file tokens in memory : this allows other libraries to avoid re-scanning file tokens */
	public function keepFileTokens() : void
	{
		if (!isset($this->file_tokens)) {
			$this->file_tokens = [];
		}
	}

	//---------------------------------------------------------------------------------------- update
	public function update() : void
	{
		$this->references = [];
		$this->scanDirectory();
		$this->classify();
		$this->save();
	}

}
