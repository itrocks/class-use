<?php
namespace ITRocks\Depend\Repository;

trait Save
{

	//---------------------------------------------------------------------------------------- PRETTY
	public const PRETTY = 4;

	//----------------------------------------------------------------------------------- $start_time
	public int $start_time;

	//------------------------------------------------------------------------------------------ save
	public function save() : void
	{
		$this->files_count = 0;
		$json_flags        = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES;
		if ($this->pretty) {
			$json_flags |= JSON_PRETTY_PRINT;
		}
		$directory = $this->cacheDirectory();
		$types     = Type::SAVE;

		foreach ($types as $type) {
			if (!is_dir("$directory/$type")) {
				mkdir("$directory/$type");
			}
			foreach ($this->{"by_$type"} as $name => $references) {
				if ($name === '') {
					continue;
				}
				$file_name = $this->cacheFileName($name, $type);
				file_put_contents($file_name, json_encode($references, $json_flags));
				touch($file_name, $this->start_time);
				$this->files_count ++;
			}
		}

		$file_name = $this->cacheFileName('files');
		file_put_contents($file_name, json_encode($this->files, $json_flags));
	}

}
