<?php
namespace ITRocks\Class_Use\Repository;

trait Search
{

	//---------------------------------------------------------------------------------------- search
	/** @var $search string[] [string $type => string $value] */
	public function search(array $search) : array
	{
		$search1 = $search2 = null;
		if (isset($search[Type::CLASS_])) {
			$type = Type::CLASS_;
			if (isset($search[Type::TYPE])) {
				$cache   = 'by_class_type';
				$tree    = [Type::CLASS_ => 0, Type::TYPE => 1, Type::USE => 2];
				$search1 = $search[Type::TYPE];
				if (isset($search[Type::USE])) {
					$search2 = $search[Type::USE];
				}
			}
			else {
				$cache = 'by_class';
				$tree  = [Type::CLASS_ => 0, Type::USE => 1, Type::TYPE => 2];
			}
		}
		elseif (isset($search[Type::USE])) {
			$type  = Type::USE;
			if (isset($search[Type::TYPE])) {
				$cache   = 'by_use_type';
				$tree    = [Type::USE => 0, Type::TYPE => 1, Type::CLASS_ => 2];
				$search1 = $search[Type::TYPE];
				if (isset($search[Type::CLASS_])) {
					$search2 = $search[Type::CLASS_];
				}
			}
			else {
				$cache = 'by_use';
				$tree  = [Type::USE => 0, Type::CLASS_ => 1, Type::TYPE => 2];
			}
		}
		else {
			return [];
		}
		$search3 = $search[Type::FILE] ?? null;

		$name = $search[$type];
		if (!isset($this->{$cache}[$name])) {
			$cache_file_name       = $this->cacheFileName($name, substr($cache, 3));
			$this->{$cache}[$name] = file_exists($cache_file_name)
				? json_decode(file_get_contents($cache_file_name), JSON_OBJECT_AS_ARRAY)
				: [];
		}

		$names      = [$name];
		$references = [];

		$array0 = $this->{$cache}[$name];
		if ($search1) {
			$array0 = [$search1 => $array0[$search1] ?? []];
		}
		foreach ($array0 as $names[1] => $array1) {
			if ($search2) {
				$array1 = [$search2 => $array1[$search2] ?? []];
			}
			foreach ($array1 as $names[2] => $array2) {
				if ($search3) {
					$array2 = [$search3 => $array2[$search3] ?? []];
				}
				foreach ($array2 as $file => $lines) {
					foreach ($lines as $line) {
						$references[] = [
							$names[$tree[Type::CLASS_]],
							$names[$tree[Type::USE]],
							$names[$tree[Type::TYPE]],
							$file,
							$line
						];
					}
				}
			}
		}
		return $references;
	}

}
