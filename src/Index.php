<?php
namespace ITRocks\Class_Use;

use ITRocks\Class_Use\Index\Cache_Directory;
use ITRocks\Class_Use\Index\Classify;
use ITRocks\Class_Use\Index\Save;
use ITRocks\Class_Use\Index\Scan;
use ITRocks\Class_Use\Index\Search;
use ITRocks\Class_Use\Token\Scanner;

class Index
{
	use Cache_Directory, Classify, Save, Scan, Search;

	//----------------------------------------------------------------------------------------- FLAGS
	public const RESET  = 2;
	public const VENDOR = 1;

	//---------------------------------------------------------------------------------------- $files
	/** @var string[] string $file_name[] */
	protected array $files = [];

	//----------------------------------------------------------------------------------- $references
	/** @var (int|string)[][] [string $file][string $class, string $use, int $type, int $line] */
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
		$this->scanner    = new Scanner;
		$this->vendor     = $flags & self::VENDOR;
		$this->setHome($home);
		$this->prepareHome();
	}

	//------------------------------------------------------------------------------------------- get
	public static function get() : static
	{
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
