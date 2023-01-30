<?php

namespace Gsdk\GuidStorage;

use Gsdk\GuidStorage\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class FileManager
{
	private readonly array $config;

	private $storage;

	public function __construct($config)
	{
		$this->config = [
			'nestingLevel' => $config['nesting_level'] ?? 1,
			'pathNameLength' => $config['path_name_length'] ?? 0,
			'dirMode' => $config['dir_mode'] ?? 0770,
			'fileMode' => $config['file_mode'] ?? 0660,
			'user' => $config['user'] ?? null,
			'group' => $config['group'] ?? null,
		];

		$this->storage = Storage::disk('files');
	}

	public function __get(string $name)
	{
		return $this->config($name);
	}

	public function __call(string $name, array $arguments)
	{
		return $this->storage->$name(...$arguments);
	}

	public function create(string $fileType, int $parentId, ?string $name = null): File
	{
		return Model::createFromParent($fileType, $parentId, $name)->file();
	}

	public function findByGuid(string $guid): ?File
	{
		$model = Model::findByGuid($guid);

		return $model?->file();
	}

	public function config(string $name)
	{
		return $this->config[$name] ?? null;
	}

	public function modeAlias($alias)
	{
		return match ($alias) {
			'file' => $this->config['fileMode'],
			'dir' => $this->config['dirMode'],
			default => $alias
		};
	}

	public function guidRelativePath(string $guid, int $part = null): string
	{
		return implode(DIRECTORY_SEPARATOR, $this->guidPaths($guid))
			. DIRECTORY_SEPARATOR . $guid
			. ($part ? '_' . $part : '');
	}

	public function guidPath(string $guid, int $part = null): string
	{
		return $this->storage->path($this->guidRelativePath($guid, $part));
	}

	public function writer(string|File $file = null): FileWriter
	{
		return new FileWriter($file);
	}

	private function guidPaths(string $guid): array
	{
		$paths = [];
		for ($i = 0; $i < $this->nestingLevel; $i++) {
			$paths[] = substr($guid, $i * $this->pathNameLength, $this->pathNameLength);
		}
		return $paths;
	}
}
