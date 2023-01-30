<?php

namespace Gsdk\GuidStorage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File as FileUtil;

class FileWriter
{
	private $model;

	public function __construct(string|File $file = null)
	{
		if (is_string($file))
			$this->guid($file);
		else if ($file)
			$this->guid($file->guid);
	}

	public function __get(string $name)
	{
		return $this->model->$name;
	}

	public function getFile(): ?File
	{
		return $this->model?->file;
	}

	public function file(File $file): static
	{
		return $this->guid($file->guid);
	}

	public function guid(string $guid): static
	{
		$model = Eloquent\Model::findByGuid($guid);
		if (!$model)
			throw new \Exception('File record not found');

		$this->model = $model;

		return $this;
	}

	public function create(string $fileType, int $parentId): static
	{
		$this->model = Eloquent\Model::createFromParent($fileType, $parentId);

		return $this;
	}

	public function attributes(array $attributes): static
	{
		$this->model->update($attributes);
		return $this;
	}

	public function put(string $contents, bool $lock = false): static
	{
		if (!$this->model)
			throw new \Exception('Model undefined');

		$manager = GuidStorageFacade::getFacadeRoot();
		$filename = $manager->guidPath($this->guid);
		$createdFlag = !file_exists($filename);

		if ($createdFlag) {
			$umask = umask(0);
			static::checkFileDirectory(FileUtil::dirname($filename), $manager->modeAlias('dir'));
		}

		file_put_contents($filename, $contents, $lock ? LOCK_EX : 0);

		if ($createdFlag)
			static::chmod($filename, $umask);

		$this->model->touch();

		return $this;
	}

	public function upload(UploadedFile $uploadedFile): static
	{
		if (!$uploadedFile->isValid())
			throw new \Exception('Uploaded file invalid');

		$this->put($uploadedFile->get());

		$this->attributes(['name' => $uploadedFile->getClientOriginalName()]);

		return $this;
	}

	private static function checkFileDirectory($path, $mode): void
	{
		if (is_dir($path))
			return;

		try {
			mkdir($path, $mode, true);
		} catch (\Exception $e) {
			throw new \Exception('Cant create folder "' . $path . '"', 0, $e);
		}
	}

	private static function chmod($filename, $umask): void
	{
		$manager = GuidStorageFacade::getFacadeRoot();
		chmod($filename, $manager->modeAlias('file'));
		umask($umask);

		if (($group = $manager->config('group')))
			chgrp($filename, $group);

		if (($user = $manager->config('user')))
			chown($filename, $user);
	}
}
