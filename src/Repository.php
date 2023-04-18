<?php
namespace ITRocks\Depend;

class Repository
{
	use Cache_Directory, Classify, Counters, Get_Data, Save, Scanner;

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

	//----------------------------------------------------------------------------------- $references
	/** @var array[] [string $class, string $dependency, string $type, int $line][int] */
	protected array $references = [];

	//-------------------------------------------------------------------------------- $refresh_files
	/** @var string[] */
	public array $refresh_files = [];

	//-------------------------------------------------------------------------------------- $refresh
	protected bool $reset;

	//------------------------------------------------------------------------------------ $singleton
	private static self $singleton;

	//----------------------------------------------------------------------------------- __construct
	public function __construct(int $flags = 0, string $home = '')
	{
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
