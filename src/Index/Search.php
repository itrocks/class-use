<?php
namespace ITRocks\Class_Use\Index;

trait Search
{

	//---------------------------------------------------------------------------------------- search
	/**
	 * @param $search      string[] string $value[string $type]
	 * @param $associative boolean Each returned record is an int|string[string $type] array
	 * @return (int|string)[][]
	 * [][string $class, int|string $type, string $use, string $file, int $line, int $token_key]
	 */
	public function search(array $search, bool $associative = false) : array
	{
		$search1 = $search2 = null;
		if (isset($search[T_TYPE])) {
			$type = T_TYPE;
			if (isset($search[T_USE])) {
				$cache   = T_TYPE_USE;
				$tree    = [T_TYPE => 0, T_USE => 1, T_CLASS => 2];
				$search1 = $search[T_USE];
				if (isset($search[T_CLASS])) {
					$search2 = $search[T_CLASS];
				}
			}
			elseif (isset($search[T_CLASS])) {
				$cache   = T_TYPE_CLASS;
				$tree    = [T_TYPE => 0, T_CLASS => 1, T_USE => 2];
				$search1 = $search[T_CLASS];
			}
			else {
				$cache = T_TYPE_USE;
				$tree  = [T_TYPE => 0, T_USE => 1, T_CLASS => 2];
			}
		}
		elseif (isset($search[T_CLASS])) {
			$cache = $type = T_CLASS;
			$tree  = [T_CLASS => 0, T_USE => 1, T_TYPE => 2];
		}
		elseif (isset($search[T_USE])) {
			$cache = $type = T_USE;
			$tree  = [T_USE => 0, T_CLASS => 1, T_TYPE => 2];
		}
		else {
			return [];
		}
		$search3 = $search[T_FILE] ?? null;

		$name = $search[$type];
		if (!isset($this->by[$cache][$name])) {
			$cache_file_name         = $this->cacheFileName($name, $cache);
			$this->by[$cache][$name] = file_exists($cache_file_name)
				? json_decode(file_get_contents($cache_file_name), JSON_OBJECT_AS_ARRAY)
				: [];
		}

		$names      = [$name];
		$references = [];

		$array0 = $this->by[$cache][$name];
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
					foreach ($lines as $token_key => $line) {
						if ($associative) {
							$references[] = [
								T_CLASS     => $names[$tree[T_CLASS]],
								T_TYPE      => $names[$tree[T_TYPE]],
								T_USE       => $names[$tree[T_USE]],
								T_FILE      => $file,
								T_LINE      => $line,
								T_TOKEN_KEY => $token_key
							];
						}
						else {
							$references[] = [
								$names[$tree[T_CLASS]],
								$names[$tree[T_TYPE]],
								$names[$tree[T_USE]],
								$file,
								$line,
								$token_key
							];
						}
					}
				}
			}
		}
		return $references;
	}

}
