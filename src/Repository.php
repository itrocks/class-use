<?php
namespace ITRocks\Depend;

use ITRocks\Depend\Repository\Cache_Directory;
use ITRocks\Depend\Repository\Classify;
use ITRocks\Depend\Repository\Counters;
use ITRocks\Depend\Repository\Get_Data;
use ITRocks\Depend\Repository\Save;
use ITRocks\Depend\Repository\Scanner;
use ITRocks\Depend\Repository\Search;

class Repository
{
	use Cache_Directory, Classify, Counters, Get_Data, Save, Scanner, Search;

	//----------------------------------------------------------------------------------------- FLAGS
	public const RESET  = 2;
	public const VENDOR = 1;

	//------------------------------------------------------------------------------------- $by_class
	/** int $line[string $class][string $dependency][string $type][string $file][int] */
	protected array $by_class = [];

	//-------------------------------------------------------------------------------- $by_class_type
	/** int $line[string $class][string $type][string $dependency][string $file][int] */
	protected array $by_class_type = [];

	//-------------------------------------------------------------------------------- $by_dependency
	/** int $line[string $dependency][string $class][string $type][string $file][int] */
	protected array $by_dependency = [];

	//--------------------------------------------------------------------------- $by_dependency_type
	/** int $line[string $dependency][string $type][string $class][string $file][int] */
	protected array $by_dependency_type = [];

	//-------------------------------------------------------------------------------------- $by_file
	/** string $reference[string $file_name][string $reference_type][int] */
	protected array $by_file = [];

	//-------------------------------------------------------------------------------- $by_type_class
	/** int $line[string $type][string $class][string $dependency][string $file][int] */
	protected array $by_type_class = [];

	//--------------------------------------------------------------------------- $by_type_dependency
	/** int $line[string $type][string $dependency][string $class][string $file][int] */
	protected array $by_type_dependency = [];

	//-------------------------------------------------------------------------------- $file_contents
	/** @var string[] [$file_name => $file_content] */
	protected array $file_contents = [];

	//---------------------------------------------------------------------------------------- $files
	/** @var string[] string $file_name[int] */
	protected array $files = [];

	//--------------------------------------------------------------------------------------- $pretty
	protected bool $pretty;

	//----------------------------------------------------------------------------------- $references
	/** @var (int|string)[][] [string $file][string $class, string $dependency, string $type, int $line] */
	protected array $references = [];

	//-------------------------------------------------------------------------------- $refresh_files
	/** @var string[] */
	public array $refresh_files = [];

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

	//---------------------------------------------------------------------------------------- update
	public function update() : void
	{
		$this->references = [];
		$this->scanDirectory();
		$this->classify();
		$this->save();
	}

}
