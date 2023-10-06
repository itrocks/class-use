<?php
namespace ITRocks\Class_Use;

use Exception;
use ITRocks\Class_Use\Index\Cache_Directory;
use ITRocks\Class_Use\Index\Classify;
use ITRocks\Class_Use\Index\Save;
use ITRocks\Class_Use\Index\Scan;
use ITRocks\Class_Use\Index\Search;
use ITRocks\Class_Use\Token\Scanner;

/** @phpstan-consistent-constructor **/
class Index
{
	use Cache_Directory, Classify, Save, Scan, Search;

	//----------------------------------------------------------------------------------------- FLAGS
	public const RESET  = 2;
	public const VENDOR = 1;

	//---------------------------------------------------------------------------------------- $files
	/** @var string[] */
	protected array $files = [];

	//----------------------------------------------------------------------------------- $references
	/**
	 * @var array<string,array<array{string,int|string,string,int,int}>>
	 * <string $file, <{string $class, int|string $type, string $use, int $line, int $token_key}>>
	 * $file: Path of the file, relative to the home path
	 */
	protected array $references = [];

	//---------------------------------------------------------------------------------------- $reset
	protected bool $reset = false;

	//------------------------------------------------------------------------------------ $singleton
	protected static self $singleton;

	//----------------------------------------------------------------------------------- __construct
	/** @throws Exception */
	public function __construct(int $flags = 0, string $home = '')
	{
		$this->pretty     = (bool)($flags & self::PRETTY);
		$this->reset      = (bool)($flags & self::RESET);
		$this->start_time = time();
		$this->scanner    = new Scanner;
		$this->vendor     = (bool)($flags & self::VENDOR);
		$this->setHome($home);
		$this->prepareHome();
	}

	//------------------------------------------------------------------------------------------- get
	public static function get() : static
	{
		/** @phpstan-ignore-next-line */
		return static::$singleton ?? (static::$singleton = new static);
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
