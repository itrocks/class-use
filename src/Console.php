<?php
namespace ITRocks\Class_Use;

use ITRocks\Class_Use\Token\Name;

class Console
{

	//--------------------------------------------------------------------------------- nameArguments
	/** @var string[] $arguments */
	protected function nameArguments(array &$arguments) : void
	{
		foreach ($arguments as $key => $argument) {
			if (!str_contains($argument, '=')) {
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
			$arguments[$name] = str_replace('/', '\\', $value);
		}
	}

	//---------------------------------------------------------------------------- quickDocumentation
	public function quickDocumentation() : string
	{
		return join("\n", [
			'Scan your PHP project for class uses',
			'',
			'usage to calculate cache : ./run [reset] [vendor] [pretty]',
			'- reset  : purge class use cache and calculate it from scratch',
			'- vendor : scan class uses into the vendor source code directory too',
			'- pretty : updated cache files use json pretty print to be human-readable',
			'usage to get class use info : ./run [class=<class>] [file=<file>] [type=<type>] [use=<class>]',
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
		if (in_array('reset', $arguments, true)) {
			$flags |= Index::RESET;
		}
		if (in_array('vendor', $arguments, true)) {
			$flags |= Index::VENDOR;
		}
		if (in_array('pretty', $arguments, true)) {
			$flags |= Index::PRETTY;
			$pretty = ' pretty';
		}
		else {
			$pretty = '';
		}

		$index = new Index($flags);
		echo ($flags & Index::RESET) ? 'reset' : 'update';
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
		echo "- saved $index->files_count files in "
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
		$options = [];
		$search  = [];
		foreach ($arguments as $key => $value) {
			if (is_string($key)) {
				$search[array_flip(Name::OF)[$key]] = $value;
			}
			else {
				$options[] = $value;
			}
		}

		$start  = microtime(true);
		$index  = Index::get();
		$result = $index->search($search);
		$stop   = microtime(true);
		if (in_array('detail', $options, true)) {
			print_r($result);
		}
		echo count($result) . " results\n";
		echo 'first duration  = ' . $this->showDuration($stop - $start) . "\n";

		$start = microtime(true);
		$index = Index::get();
		$index->search($search);
		echo 'second duration = ' . $this->showDuration(microtime(true) - $start) . "\n";

		$start = microtime(true);
		$index = Index::get();
		$index->search($search);
		echo 'third duration  = ' . $this->showDuration(microtime(true) - $start) . "\n";

		echo 'memory = ' . ceil(memory_get_peak_usage(true) / 1024 / 1024) . " Mo\n";
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
