<?php

namespace Gsdk\GuidStorage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class FileManager {

	private $storage;

	private array $configs;

	public function __construct($config) {
		$this->storage = Storage::disk($config->storage_disk ?? 'files');
		$this->configs = [
			'nestingLevel' => $config->nesting_level ?? 1,
			'pathNameLength' => $config->path_name_length ?? 0,
			'dirMode' => $config->dir_mode ?? 0770,
			'fileMode' => $config->file_mode ?? 0660,
			'user' => $config->user,
			'group' => $config->group,
		];
	}

	public function __get(string $name) {
		return $this->configs[$name] ?? null;
	}

	public function path(string|File $guid, int $part = null): string {
		return $this->storage->path($this->getFileRelativePath($guid));
	}

	public function modeAlias($alias) {
		return match ($alias) {
			'file' => 0660,
			'dir' => 0770,
			default => $alias
		};
		/*if (($group = self::config('group')))
			chgrp($filename, $group);

		if (($user = self::config('user')))
			chown($filename, $user);*/
	}

	public function saveFileFromTemp(File $file, $tempname, array $attributes = []): void {
		(new Support\FileUpdater($file))
			//->content($content)
			->attributes($attributes)
			->save();
	}

	public function saveFileContent(File $file, $content, array $attributes = []): void {
		(new Support\FileUpdater($file))
			->content($content)
			->attributes($attributes)
			->save();
	}

	public function saveFileFromUpload(File $file, UploadedFile $uploadFile): void {
		if (!$uploadFile->isValid())
			throw new \Exception('Uploaded file invalid');

		$this->saveFileContent($file, $uploadFile->get(), ['name' => $uploadFile->getClientOriginalName()]);
	}

	private function guidPaths(string $guid): array {
		$paths = [];

		for ($i = 0; $i < $this->nestingLevel; $i++) {
			$paths[] = substr($guid, $i * $this->pathNameLength, $this->pathNameLength);
		}

		return $paths;
	}

	private function getFileRelativePath(string|File $guid): string {
		if (!is_string($guid))
			$guid = $guid->guid;

		return implode(DIRECTORY_SEPARATOR, $this->getPaths($guid))
			. DIRECTORY_SEPARATOR . $guid;
	}
}