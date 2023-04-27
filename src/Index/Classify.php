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
			if (!$references) {
				$this->by[T_FILE][$file_name] = [];
			}
			foreach ($references as $reference) {
				[$class, $type, $use, $line, $token_key] = $reference;
				if ($class !== '') {
					$this->by[T_FILE][$file_name][T_CLASS][$class] = true;
					$this->by[T_CLASS]     [$class][$use][$type][$file_name][$token_key] = $line;
					$this->by[T_CLASS_TYPE][$class][$type][$use][$file_name][$token_key] = $line;
				}
				if ($use !== '') {
					$this->by[T_FILE][$file_name][T_USE][$use] = true;
					$this->by[T_USE]     [$use][$class][$type][$file_name][$token_key] = $line;
					$this->by[T_USE_TYPE][$use][$type][$class][$file_name][$token_key] = $line;
				}
				$this->by[T_FILE][$file_name][T_TYPE][$type] = true;
				$this->by[T_TYPE_CLASS][$type][$class][$use][$file_name][$token_key] = $line;
				$this->by[T_TYPE_USE]  [$type][$use][$class][$file_name][$token_key] = $line;
			}
			foreach ($this->by[T_FILE][$file_name] as &$values) {
				$values = array_keys($values);
			}
		}
	}

	//--------------------------------------------------------------------------------- loadAndFilter
	protected function loadAndFilter(string $file_name) : void
	{
		if (!file_exists($cache_file_name = $this->cacheFileName($file_name, T_FILE))) {
			return;
		}
		foreach (
			json_decode(file_get_contents($cache_file_name), JSON_OBJECT_AS_ARRAY) as $type => $values
		) {
			foreach (static::EXTENDS[$type] as $type) {
				foreach ($values as $value) {
					// load
					$is_set = isset($this->by[$type][$value]);
					if (!$is_set && file_exists($cache_file_name = $this->cacheFileName($value, $type))) {
						$is_set = true;
						$this->by[$type][$value] = json_decode(
							file_get_contents($cache_file_name), JSON_OBJECT_AS_ARRAY
						);
					}
					if (!$is_set) {
						continue;
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
