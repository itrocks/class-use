<?php
namespace ITRocks\Depend\Repository;

trait Search
{

	//---------------------------------------------------------------------------------------- search
	/** @var $search string[] [string $type => string $value] */
	public function search(array $search) : array
	{
		if (isset($search[Type::CLASS_])) {
			$type = Type::CLASS_;
			if (isset($search[Type::TYPE])) {
				$cache = 'by_class_type';
				$tree  = [Type::CLASS_ => 0, Type::TYPE => 1, Type::DEPENDENCY => 2];
				if (isset($search[Type::DEPENDENCY])) {
					$search2 = $search[Type::DEPENDENCY];
				}
			}
			else {
				$cache = 'by_class';
				$tree  = [Type::CLASS_ => 0, Type::DEPENDENCY => 1, Type::TYPE => 2];
			}
		}
		elseif (isset($search[Type::DEPENDENCY])) {
			$type  = Type::DEPENDENCY;
			if (isset($search[Type::TYPE])) {
				$cache = 'by_dependency_type';
				$tree  = [Type::DEPENDENCY => 0, Type::TYPE => 1, Type::CLASS_ => 2];
				if (isset($search[Type::CLASS_])) {
					$search2 = $search[Type::CLASS_];
				}
			}
			else {
				$cache = 'by_dependency';
				$tree  = [Type::DEPENDENCY => 0, Type::CLASS_ => 1, Type::TYPE => 2];
			}
		}
		else {
			return [];
		}
		$search3 = $search[Type::FILE] ?? null;

		$name = $search[$type];
		if (!isset($this->{$cache}[$name])) {
			$cache_file_name     = $this->cacheFileName($name, $type);
			$this->{$cache}[$name] = file_exists($cache_file_name)
				? json_decode(file_get_contents($cache_file_name), JSON_OBJECT_AS_ARRAY)
				: [];
		}

		$names      = [$name];
		$references = [];
		foreach ($this->{$cache}[$name] as $names[1] => $array1) {
			foreach ($array1 as $names[2] => $array2) {
				if (isset($search2) && ($search2 !== $names[2])) continue;
				foreach ($array2 as $file => $lines) {
					if (isset($search3) && ($search3 !== $file)) continue;
					foreach ($lines as $line) {
						$references[] = [
							$names[$tree[Type::CLASS_]],
							$names[$tree[Type::DEPENDENCY]],
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
