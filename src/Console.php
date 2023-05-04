<?php
namespace ITRocks\Class_Use;

use Error;
use ITRocks\Class_Use\Token\Name;

class Console
{

	//------------------------------------------------------------------------------ SEARCH ARGUMENTS
	const ASSOCIATIVE = 'associative';
	const BENCHMARK   = 'benchmark';
	const DATA        = 'data';
	const TOTAL       = 'total';

	//------------------------------------------------------------------------------- CLASS_ARGUMENTS
	const CLASS_ARGUMENTS = ['class', 'type', 'use'];

	//------------------------------------------------------------------------------------------ HOME
	const HOME = 'home';

	//-------------------------------------------------------------------------------- SCAN ARGUMENTS
	const PRETTY = 'pretty';
	const RESET  = 'reset';
	const UPDATE = 'update';
	const VENDOR = 'vendor';

	//--------------------------------------------------------------------------------- nameArguments
	/** @param $arguments string[] */
	protected function nameArguments(array &$arguments) : void
	{
		foreach ($arguments as $key => &$argument) {
			if (!str_contains($argument, '=')) {
				$argument = strtolower($argument);
				continue;
			}
			unset($arguments[$key]);
			[$name, $value] = explode('=', $argument);
			if ($name === '') {
				$name = $arguments[$key - 1];
				unset($arguments[$key - 1]);
			}
			if ($value === '') {
				$value = $arguments[$key + 1];
				unset($arguments[$key + 1]);
			}
			$name = strtolower($name);
			$arguments[$name] = in_array($name, static::CLASS_ARGUMENTS, true)
				? str_replace('/', '\\', $value)
				: $value;
		}
	}

	//-------------------------------------------------------------------------------------- newIndex
	protected function newIndex(int $flags = 0, string $home = '') : Index
	{
		return new Index($flags, $home);
	}

	//---------------------------------------------------------------------------- quickDocumentation
	public function quickDocumentation() : string
	{
		return join("\n", [
			'Scan your PHP project for class uses',
			'',
			'usage to calculate cache : ./run [home=/path/to/project] [pretty] [reset] [vendor]',
			'- home:   the project home directory where to scan classes into'
				. ' (default : current/parent project directory)',
			'- pretty: updated cache files use json pretty print to be human-readable',
			'- reset:  purge class use cache and calculate it from scratch',
			'- vendor: scan class uses into the vendor source code directory too',
			'usage to search class uses: ./run [class=<class>] [file=<file>] [type=<type>] [use=<class>]',
			''
		]);
	}

	//------------------------------------------------------------------------------------------- run
	/** @param $arguments string[] */
	public function run(array $arguments) : void
	{
		$this->nameArguments($arguments);
		foreach (Index::SAVE as $type) {
			if (isset($arguments[Name::OF[$type]])) {
				$this->search($arguments);
				return;
			}
		}
		$this->scan($arguments);
	}

	//------------------------------------------------------------------------------------------ scan
	/** @param $arguments string[] */
	protected function scan(array $arguments) : void
	{
		$flags = 0;
		if (in_array(self::RESET, $arguments, true)) {
			$flags |= Index::RESET;
		}
		if (in_array(self::VENDOR, $arguments, true)) {
			$flags |= Index::VENDOR;
		}
		if (in_array(self::PRETTY, $arguments, true)) {
			$flags |= Index::PRETTY;
			$pretty = ' ' . self::PRETTY;
		}
		else {
			$pretty = '';
		}

		$index = $this->newIndex($flags, $arguments[self::HOME] ?? '');
		echo ($flags & Index::RESET) ? self::RESET : self::UPDATE;
		if ($flags & Index::VENDOR) {
			echo ' with vendor';
		}
		echo ' from project directory ' . $index->getHome();
		echo "\n";

		echo date('Y-m-d H:i:s') . "\n";
		$total = microtime(true);

		$start = microtime(true);
		$index->scanDirectory();
		echo "- scanned $index->directories_count directories and $index->files_count files in "
			. $this->showDuration(microtime(true) - $start) . "\n";

		$start = microtime(true);
		$index->classify();
		echo "- classified $index->references_count references in "
			. $this->showDuration(microtime(true) - $start) . "\n";

		$start = microtime(true);
		$index->save();
		echo "- saved $index->saved_files_count files in "
			. $this->showDuration(microtime(true) - $start) . "\n";

		echo date('Y-m-d H:i:s') . "\n";
		echo 'duration = ' . $this->showDuration(microtime(true) - $total) . "\n";
		echo 'memory   = ' . ceil(memory_get_peak_usage(true) / 1024 / 1024) . " Mo\n";
		echo "cache$pretty written into directory " . $index->getCacheDirectory() . "\n";
	}

	//---------------------------------------------------------------------------------------- search
	/** @param $arguments string[] [int $type => string $name] */
	protected function search(array $arguments) : void
	{
		$of_name = array_flip(Name::OF);
		$options = [];
		$search  = [];
		foreach ($arguments as $key => $value) {
			if (is_string($key)) {
				$search[$of_name[$key]] = $value;
			}
			elseif ($value === 'associative') {
				$options[$value] = T_STRING;
			}
			else {
				$options[$value] = true;
			}
		}
		if ($value = ($search[T_TYPE] ?? false)) {
			if (str_starts_with($value, 't_') || str_starts_with($value, 'T_')) {
				try {
					$search[T_TYPE] = constant(strtoupper($value));
				}
				catch (Error) {
				}
			}
			else {
				$search[T_TYPE] = $of_name[str_replace('_', '-', $value)] ?? $value;
			}
		}
		if ($options[self::ASSOCIATIVE] ?? $options[self::PRETTY] ?? false) {
			$options[self::DATA] = true;
		}

		$start  = microtime(true);
		$index  = $this->newIndex(0, $arguments[self::HOME] ?? '');
		$result = $index->search($search, $options[self::ASSOCIATIVE] ?? false);
		$stop   = microtime(true);
		if ($options[self::DATA] ?? false) {
			echo json_encode($result, ($options[self::PRETTY] ?? false) ? JSON_PRETTY_PRINT : 0);
			if ($options[self::BENCHMARK] ?? $options[self::TOTAL] ?? false) {
				echo "\n";
			}
		}
		if (($options[self::TOTAL] ?? false) || !($options[self::DATA] ?? false)) {
			echo count($result) . " results\n";
		}
		if ($options[self::BENCHMARK] ?? false) {
			echo 'duration  = ' . $this->showDuration($stop - $start) . "\n";
			for ($i = 2; $i < 8; $i ++) {
				$start = microtime(true);
				$index->search($search, $options[self::ASSOCIATIVE] ?? false);
				echo "duration $i = " . $this->showDuration(microtime(true) - $start) . "\n";
			}
			echo 'memory = ' . ceil(memory_get_peak_usage(true) / 1024 / 1024) . " Mo\n";
		}
	}

	//---------------------------------------------------------------------------------- showDuration
	protected function showDuration(float $duration, $decimals = 3) : string
	{
		if ($duration < .1) {
			return round($duration * 1000, $decimals) . ' ms';
		}
		return round($duration, $decimals) . ' seconds';
	}

}
