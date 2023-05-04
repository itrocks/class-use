<?php
namespace ITRocks\Class_Use;

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
	/** @var string[] string $file_name[] */
	protected array $files = [];

	//----------------------------------------------------------------------------------- $references
	/**
	 * @var array< string, array< array{ string, int|string, string, int, int } > >
	 * [string $file => [string $class, int|string $type, string $use, int $line, int $token_key]]
	 */
	protected array $references = [];

	//---------------------------------------------------------------------------------------- $reset
	protected bool $reset;

	//------------------------------------------------------------------------------------ $singleton
	protected static self $singleton;

	//----------------------------------------------------------------------------------- __construct
	public function __construct(int $flags = 0, string $home = '')
	{
		$this->pretty     = boolval($flags & self::PRETTY);
		$this->reset      = boolval($flags & self::RESET);
		$this->start_time = time();
		$this->scanner    = new Scanner;
		$this->vendor     = boolval($flags & self::VENDOR);
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
