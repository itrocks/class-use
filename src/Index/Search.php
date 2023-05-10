<?php
namespace ITRocks\Class_Use\Index;

trait Search
{

	//--------------------------------------------------------------------------------- RESULT TOKENS
	public const INT_RESULTS    = [self::T_TYPE, self::T_LINE, self::T_TOKEN];
	public const STRING_RESULTS = [self::T_CLASS, self::T_TYPE, self::T_USE, self::T_FILE];

	//--------------------------------------------------------------------------------- SEARCH_TOKENS
	public const SEARCH_TOKENS = [self::T_CLASS, self::T_FILE, self::T_TYPE, self::T_USE];

	//---------------------------------------------------------------------------------------- TOKENS
	public const T_CLASS  = T_CLASS;
	public const T_FILE   = T_FILE;
	public const T_LINE   = T_LINE;
	public const T_STRING = T_STRING;
	public const T_TOKEN  = T_TOKEN_KEY;
	public const T_TYPE   = T_TYPE;
	public const T_USE    = T_USE;

	//---------------------------------------------------------------------------------------- search
	/**
	 * @param array<value-of<self::SEARCH_TOKENS>,int|string> $search
	 * @param bool|self::T_STRING|self::T_TYPE $associative
	 * @return array<(
	 *   $associative is self::T_STRING
	 *   ? array{'class':string,'type':int|string,'use':string,'file':string,'line':int,'token':int}
	 *   : (
	 *     $associative is self::T_TYPE|true
	 *     ? array<value-of<self::INT_RESULTS>,int>|array<value-of<self::STRING_RESULTS>,string>
	 *     : list{string,int|string,string,string,int,int}))>
	 * <{string $class, int|string $type, string $use, string $file, int $line, int $token_key}>
	 */
	public function search(array $search, bool|int $associative = false) : array
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
			/** @phpstan-ignore-next-line The cache file has been written from the same structure */
			$this->by[$cache][$name] = file_exists($cache_file_name)
				? json_decode(file_get_contents($cache_file_name) ?: '', true)
				: [];
		}

		/** @var array<int|string> $names */
		$names      = [$name];
		$references = [];

		/** @var array<int|string,array<int|string,array<string,array<int,int>>>> $array0 */
		$array0 = $this->by[$cache][$name];
		if (isset($search1)) {
			/** @var array<int|string,array<int|string,array<string,array<int,int>>>> $array0 */
			$array0 = [$search1 => $array0[$search1] ?? []];
		}
		foreach ($array0 as $names[1] => $array1) {
			if (isset($search2)) {
				$array1 = [$search2 => $array1[$search2] ?? []];
			}
			foreach ($array1 as $names[2] => $array2) {
				if (isset($search3)) {
					$array2 = [$search3 => $array2[$search3] ?? []];
				}
				/** @var string $file */
				foreach ($array2 as $file => $lines) {
					foreach ($lines as $token => $line) {
						$references[] = match($associative) {
							T_TYPE, true => [
								T_CLASS     => strval($names[$tree[T_CLASS]]),
								T_TYPE      => intval($names[$tree[T_TYPE]]),
								T_USE       => strval($names[$tree[T_USE]]),
								T_FILE      => $file,
								T_LINE      => intval($line),
								T_TOKEN_KEY => intval($token)
							],
							T_STRING => [
								'class' => strval($names[$tree[T_CLASS]]),
								'type'  => intval($names[$tree[T_TYPE]]),
								'use'   => strval($names[$tree[T_USE]]),
								'file'  => $file,
								'line'  => intval($line),
								'token' => intval($token)
							],
							default => [
								strval($names[$tree[T_CLASS]]),
								intval($names[$tree[T_TYPE]]),
								strval($names[$tree[T_USE]]),
								$file,
								intval($line),
								intval($token)
							]
						};
					}
				}
			}
		}
		return $references;
	}

}
