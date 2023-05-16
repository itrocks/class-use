<?php
namespace ITRocks\Class_Use\Index;

trait Classify
{

	//--------------------------------------------------------------------------------------- EXTENDS
	/** @var array<int,array<int>> */
	const EXTENDS = [
		T_CLASS => [T_CLASS,      T_CLASS_TYPE],
		T_USE   => [T_USE,        T_USE_TYPE],
		T_TYPE  => [T_TYPE_CLASS, T_TYPE_USE]
	];

	//------------------------------------------------------------------------------------------- $by
	/**
	 * @var array<int,array<int|string,array<int|string,array<int|string,array<int|string,array<int,int>|int>>>>>
	 * <int=T_CLASS,      <string $class, <string $use, <int|string $type, <string $file, <int
	 * $token, int $line>>>>>>
	 * <int=T_CLASS_TYPE, <string $class, <int|string $type, <string $use, <string $file, <int
	 * $token, int $line>>>>>>
	 * <int=T_FILE,       <string $file_name, <int $reference_type, <int $token_key, int $line>>>>
	 * <int=T_TYPE_CLASS, <int|string $type, <string $class, <string $use, <string $file, <int
	 * $token, int $line>>>>>>
	 * <int=T_TYPE_USE,   <int|string $type, <string $use, <string $class, <string $file, <int
	 * $token, int $line>>>>>>
	 * <int=T_USE,        <string $use, <string $class, <int|string $type, <string $file, <int
	 * $token, int $line>>>>>>
	 * <int=T_USE_TYPE,   <string $use, <int|string $type, <string $class, <string $file, <int
	 * $token, int $line>>>>>>
	 */
	protected array $by = [
		T_CLASS      => [],
		T_CLASS_TYPE => [],
		T_FILE       => [],
		T_TYPE_CLASS => [],
		T_TYPE_USE   => [],
		T_USE        => [],
		T_USE_TYPE   => []
	];

	//-------------------------------------------------------------------------------------- classify
	public function classify() : void
	{
		foreach ($this->references as $file_name => $references) {
			if (!$this->reset) {
				$this->loadAndFilter($file_name);
			}
			foreach ($references as $reference) {
				[$class, $type, $use, $line, $token_key] = $reference;
				/** @noinspection DuplicatedCode */
				if ($class !== '') {
					$this->by[T_FILE][$file_name][T_CLASS][$class][$token_key] = $line;
					$this->by[T_CLASS]     [$class][$use][$type][$file_name][$token_key] = $line;
					$this->by[T_CLASS_TYPE][$class][$type][$use][$file_name][$token_key] = $line;
				}
				/** @noinspection DuplicatedCode */
				if ($use !== '') {
					$this->by[T_FILE][$file_name][T_USE][$use][$token_key] = $line;
					$this->by[T_USE]     [$use][$class][$type][$file_name][$token_key] = $line;
					$this->by[T_USE_TYPE][$use][$type][$class][$file_name][$token_key] = $line;
				}
				/** @noinspection DuplicatedCode */
				$this->by[T_FILE][$file_name][T_TYPE][$type][$token_key] = $line;
				$this->by[T_TYPE_CLASS][$type][$class][$use][$file_name][$token_key] = $line;
				$this->by[T_TYPE_USE]  [$type][$use][$class][$file_name][$token_key] = $line;
			}
		}
	}

	//--------------------------------------------------------------------------------- loadAndFilter
	protected function loadAndFilter(string $file_name) : void
	{
		// load file references
		if (isset($this->by[T_FILE][$file_name])) {
			$file_references = $this->by[T_FILE][$file_name];
			unset($this->by[T_FILE][$file_name]);
		}
		elseif (file_exists($cache_file_name = $this->cacheFileName($file_name, T_FILE))) {
			$file_references = json_decode(file_get_contents($cache_file_name) ?: '', true);
		}
		else {
			return;
		}
		/** @var array<int,array<int|string,array<int,int>>> $file_references */
		// load and filter all referencing files
		foreach ($file_references as $main_type => $values) {
			foreach (static::EXTENDS[$main_type] as $type) {
				foreach (array_keys($values) as $value) {
					// load
					if (!isset($this->by[$type][$value])) {
						// The case of a reference into T_FILE to a non-existing cache file should never happen.
						// It would throw an error you may solve with a reset of the index.
						/** @phpstan-ignore-next-line The cache file has been written using a valid structure */
						$this->by[$type][$value] = json_decode(
							file_get_contents($this->cacheFileName($value, $type)) ?: '', true
						);
					}
					// filter
					/** @var array<int|string,array<int|string,array<string,array<int,int>>>> $references
					 * May have been decoded from the json file content written using a valid structure */
					$references =& $this->by[$type][$value];
					foreach ($references as $key => &$references1) {
						foreach ($references1 as $key1 => &$references2) {
							unset($references2[$file_name]);
							if ($references2 === []) {
								unset($references1[$key1]);
							}
						}
						if ($references1 === []) {
							unset($references[$key]);
						}
					}
				}
			}
		}
	}

}
