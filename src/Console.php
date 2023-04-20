<?php
namespace ITRocks\Depend;

use ITRocks\Depend\Repository\Type;

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
			'Scan your PHP project for class uses / internal dependencies',
			'',
			'usage to calculate cache : ./run [reset] [vendor] [pretty]',
			'- reset  : purge dependency cache and calculate it from scratch',
			'- vendor : include dependencies from the vendor source code directory',
			'- pretty : updated cache files use json pretty print to be human-readable',
			'usage to get dependency info : ./run [type] [name]',
			''
		]);
	}

	//------------------------------------------------------------------------------------------- run
	/** @param $arguments string[] */
	public function run(array $arguments) : void
	{
		$this->nameArguments($arguments);
		foreach (Type::SAVE as $type) {
			if (isset($arguments[$type])) {
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
			$flags |= Repository::RESET;
		}
		if (in_array('vendor', $arguments, true)) {
			$flags |= Repository::VENDOR;
		}
		if (in_array('pretty', $arguments, true)) {
			$flags |= Repository::PRETTY;
			$pretty = ' pretty';
		}
		else {
			$pretty = '';
		}

		$repository = new Repository($flags);
		echo ($flags & Repository::RESET) ? 'reset' : 'update';
		if ($flags & Repository::VENDOR) {
			echo ' with vendor';
		}
		echo ' from project directory ' . $repository->getHome();
		echo "\n";

		echo date('Y-m-d H:i:s') . "\n";
		$total = microtime(true);

		$start = microtime(true);
		$repository->scanDirectory();
		echo "- scanned $repository->directories_count directories and $repository->files_count files in "
			. $this->showDuration(microtime(true) - $start) . "\n";

		$start = microtime(true);
		$repository->classify();
		echo "- classified $repository->references_count references in "
			. $this->showDuration(microtime(true) - $start) . "\n";

		$start = microtime(true);
		$repository->save();
		echo "- saved $repository->files_count files in "
			. $this->showDuration(microtime(true) - $start) . "\n";

		echo date('Y-m-d H:i:s') . "\n";
		echo 'duration = ' . $this->showDuration(microtime(true) - $total) . "\n";
		echo 'memory   = ' . ceil(memory_get_peak_usage(true) / 1024 / 1024) . " Mo\n";
		echo "cache$pretty written into directory " . $repository->getCacheDirectory() . "\n";
	}

	//---------------------------------------------------------------------------------------- search
	/** @param $search string[] [string $type => string $name] */
	protected function search(array $search) : void
	{
		$start      = microtime(true);
		$repository = Repository::get();
		$result     = $repository->search($search);
		$stop       = microtime(true);
		if (in_array('detail', $search, true)) {
			print_r($result);
		}
		echo count($result) . " results\n";
		echo 'first duration  = ' . $this->showDuration($stop - $start) . "\n";

		$start      = microtime(true);
		$repository = Repository::get();
		$repository->search($search);
		echo 'second duration = ' . $this->showDuration(microtime(true) - $start) . "\n";

		$start      = microtime(true);
		$repository = Repository::get();
		$repository->search($search);
		echo 'third duration  = ' . $this->showDuration(microtime(true) - $start) . "\n";

		echo 'memory = ' . ceil(memory_get_peak_usage(true) / 1024 / 1024) . " Mo\n";
	}

	//---------------------------------------------------------------------------------- showDuration
	protected function showDuration(float $duration) : string
	{
		if ($duration < .1) {
			return round($duration * 1000, 3) . ' ms';
		}
		return round($duration, 3) . ' seconds';
	}

}
