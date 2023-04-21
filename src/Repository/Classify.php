<?php
namespace ITRocks\Class_Use\Repository;

trait Classify
{

	//------------------------------------------------------------------------------------- $by_class
	/** int $line[string $class][string $use][string $type][string $file][int] */
	protected array $by_class = [];

	//-------------------------------------------------------------------------------- $by_class_type
	/** int $line[string $class][string $type][string $use][string $file][int] */
	protected array $by_class_type = [];

	//-------------------------------------------------------------------------------------- $by_file
	/** string $reference[string $file_name][string $reference_type][int] */
	protected array $by_file = [];

	//-------------------------------------------------------------------------------- $by_type_class
	/** int $line[string $type][string $class][string $use][string $file][int] */
	protected array $by_type_class = [];

	//---------------------------------------------------------------------------------- $by_type_use
	/** int $line[string $type][string $use][string $class][string $file][int] */
	protected array $by_type_use = [];

	//--------------------------------------------------------------------------------------- $by_use
	/** int $line[string $use][string $class][string $type][string $file][int] */
	protected array $by_use = [];

	//---------------------------------------------------------------------------------- $by_use_type
	/** int $line[string $use][string $type][string $class][string $file][int] */
	protected array $by_use_type = [];

	//-------------------------------------------------------------------------------------- classify
	public function classify() : void
	{
		$this->by_file       = [];
		$this->by_class      = [];
		$this->by_class_type = [];
		$this->by_use        = [];
		$this->by_use_type   = [];

		foreach ($this->references as $file_name => $references) {
			$file_name = substr($file_name, $this->home_length);
			if (!$this->reset) {
				$this->loadAndFilter($file_name);
			}
			if (!$references) {
				$this->by_file[$file_name] = [];
			}
			foreach ($references as $reference) {
				[$class, $use, $type, $line, $token_key] = $reference;
				if ($class !== '') {
					$this->by_file[$file_name][Type::CLASS_][$class] = true;
					$this->by_class      [$class][$use][$type][$file_name][$token_key] = $line;
					$this->by_class_type [$class][$type][$use][$file_name][$token_key] = $line;
				}
				if ($use !== '') {
					$this->by_file[$file_name][Type::USE][$use] = true;
					$this->by_use      [$use][$class][$type][$file_name][$token_key] = $line;
					$this->by_use_type [$use][$type][$class][$file_name][$token_key] = $line;
				}
				$this->by_file[$file_name][Type::TYPE][$type] = true;
				$this->by_type_class [$type][$class][$use][$file_name][$token_key] = $line;
				$this->by_type_use   [$type][$use][$class][$file_name][$token_key] = $line;
			}
			foreach ($this->by_file[$file_name] as &$values) {
				$values = array_keys($values);
			}
		}
	}

	//--------------------------------------------------------------------------------- loadAndFilter
	protected function loadAndFilter(string $file_name) : void
	{
		if (!file_exists($cache_file_name = $this->cacheFileName($file_name, 'file'))) {
			return;
		}
		foreach (
			json_decode(file_get_contents($cache_file_name), JSON_OBJECT_AS_ARRAY) as $type => $values
		) {
			foreach (Type::EXTENDS[$type] as $type) {
				foreach ($values as $value) {
					// load
					$is_set = isset($this->{"by_$type"}[$value]);
					if (!$is_set && file_exists($cache_file_name = $this->cacheFileName($value, $type))) {
						$is_set = true;
						$this->{"by_$type"}[$value] = json_decode(
							file_get_contents($cache_file_name), JSON_OBJECT_AS_ARRAY
						);
					}
					if (!$is_set) {
						continue;
					}
					// filter
					$references =& $this->{"by_$type"}[$value];
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
