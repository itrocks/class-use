<?php
namespace ITRocks\Class_Use\Index;

trait Classify
{

	//--------------------------------------------------------------------------------------- EXTENDS
	const EXTENDS = [
		T_CLASS => [T_CLASS,      T_CLASS_TYPE],
		T_USE   => [T_USE,        T_USE_TYPE],
		T_TYPE  => [T_TYPE_CLASS, T_TYPE_USE]
	];

	//------------------------------------------------------------------------------------------- $by
	/**
	 * T_CLASS_     => int $line[string $class][string $use][string $type][string $file][int]
	 * T_CLASS_TYPE => int $line[string $class][string $type][string $use][string $file][int]
	 * T_FILE       => string $reference[string $file_name][string $reference_type][int]
	 * T_TYPE_CLASS => int $line[string $type][string $class][string $use][string $file][int]
	 * T_TYPE_USE   => int $line[string $type][string $use][string $class][string $file][int]
	 * T_USE        => int $line[string $use][string $class][string $type][string $file][int]
	 * T_USE_TYPE   => int $line[string $use][string $type][string $class][string $file][int]
	 */
	protected array $by;

	//-------------------------------------------------------------------------------------- classify
	public function classify() : void
	{
		$this->by = [
			T_CLASS      => [],
			T_CLASS_TYPE => [],
			T_FILE       => [],
			T_TYPE_CLASS => [],
			T_TYPE_USE   => [],
			T_USE        => [],
			T_USE_TYPE   => []
		];

		foreach ($this->references as $file_name => $references) {
			$file_name = substr($file_name, $this->home_length);
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
			$file_references = json_decode(file_get_contents($cache_file_name), true);
		}
		else {
			return;
		}
		// load and filter all referencing files
		foreach ($file_references as $type => $values) {
			foreach (static::EXTENDS[$type] as $type) {
				foreach (array_keys($values) as $value) {
					// load
					if (!isset($this->by[$type][$value])) {
						// The case of a reference into T_FILE to a non-existing cache file should never happen.
						// It would throw an error you may solve with a reset of the index.
						$this->by[$type][$value] = json_decode(
							file_get_contents($this->cacheFileName($value, $type)), true
						);
					}
					// filter
					$references =& $this->by[$type][$value];
					foreach ($references as $key => &$references1) {
						foreach ($references1 as $key1 => &$references2) {
							unset($references2[$file_name]);
							if (!$references2) {
								unset($references1[$key1]);
							}
						}
						if (!$references1) {
							unset($references[$key]);
						}
					}
				}
			}
		}
	}

}
