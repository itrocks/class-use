<?php
namespace ITRocks\Class_Use\Index;

use ITRocks\Class_Use\Token\Name;

trait Save
{

	//---------------------------------------------------------------------------------------- PRETTY
	public const PRETTY = 4;

	//------------------------------------------------------------------------------------------ SAVE
	const SAVE = [T_CLASS, T_CLASS_TYPE, T_FILE, T_TYPE_CLASS, T_TYPE_USE, T_USE, T_USE_TYPE];

	//--------------------------------------------------------------------------------------- $pretty
	protected bool $pretty;

	//---------------------------------------------------------------------------- $saved_files_count
	public int $saved_files_count;

	//----------------------------------------------------------------------------------- $start_time
	public int $start_time;

	//------------------------------------------------------------------------------------------ save
	public function save() : void
	{
		$this->saved_files_count = 0;
		$json_flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES;
		if ($this->pretty) {
			$json_flags |= JSON_PRETTY_PRINT;
		}
		$directory = $this->getCacheDirectory();

		foreach (static::SAVE as $type) {
			$type_directory = Name::OF[$type];
			if (!is_dir("$directory/$type_directory")) {
				mkdir("$directory/$type_directory");
			}
			foreach ($this->by[$type] as $name => $references) {
				if ($name === '') {
					continue;
				}
				$file_name = $this->cacheFileName($name, $type);
				file_put_contents($file_name, json_encode($references, $json_flags));
				touch($file_name, $this->start_time);
				$this->saved_files_count ++;
			}
		}

		$file_name = $this->cacheFileName('files');
		file_put_contents($file_name, json_encode($this->files, $json_flags));
	}

}
