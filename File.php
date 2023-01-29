<?php

namespace Gsdk\GuidStorage;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;

class File extends Eloquent\Model
{

	protected $fullname;
	protected $entity;

	public static function storage()
	{
		return Filesystem::storage();
	}

	protected static function boot()
	{
		parent::boot();

		static::creating(function ($file) {
			$file->type = static::class;
			$file->guid = Filesystem::generateGuid();
		});

		static::retrieved(function ($file) {
			$file->fullname = Filesystem::getDestination($file->guid);
		});

		static::deleting(function ($file) {
			if (!$file->unlink())
				return false;

			$file->fullname = null;

			return true;
		});

		static::addGlobalScope('main', function (Builder $builder) {
			if (__CLASS__ !== static::class)
				$builder->whereType(static::class);
		});
	}

	public static function createFromEntity($entity, array $data = []): ?static
	{
		if (!$entity->id)
			throw new Exception('Entity empty');

		$data['entity_id'] = $entity->id;
		$data['entity_type'] = get_class($entity);

		$file = static::create($data);
		$file->fullname = Filesystem::getDestination($file->guid);

		return $file;
	}

	public static function findById($id)
	{
		return static::findByQuery(Eloquent\Model::where('id', $id));
	}

	public static function findByGuid($guid)
	{
		return static::findByQuery(Eloquent\Model::where('guid', $guid));
	}

	public static function findByEntity($entity)
	{
		return static::findByQuery(Eloquent\Model::whereEntity($entity));
	}

	private static function findByQuery($query)
	{
		if (static::class !== __CLASS__)
			$query->where('type', static::class);

		$model = $query->first();
		if (!$model)
			return null;

		$file = new $model->type();
		$file->forceFill($model->getAttributes());
		$file->fullname = Filesystem::getDestination($model->guid);
		$file->exists = true;

		return $file;
	}

	public function __get($name)
	{
		switch ($name) {
			case 'fullname':
				return $this->fullname;
			case 'name':
				$name = parent::__get($name);
				if ($name)
					return $name;
				else if ($this->mime_type)
					return $this->guid . '.' . Filesystem::mimeToExtension($this->mime_type);
				else
					return $this->guid;
		}

		return parent::__get($name);
	}

	public function lastModified(): string
	{
		return $this->mtime;
	}

	public function upload(UploadedFile $uploadFile)
	{
		Filesystem::saveFileFromUpload($this, $uploadFile);
	}

	public function exists(): bool
	{
		return $this->fullname && self::storage()->exists($this->fullname);
	}

	public function content()
	{
		return $this->exists() ? self::storage()->get($this->fullname) : null;
	}

	public function unlink()
	{
		return $this->exists() && self::storage()->delete($this->fullname);
	}

	public function download($name = null, array $headers = [])
	{
		$headers = [
			'Content-Type: ' . $this->mime_type,
		];

		return self::storage()->download($this->fullname, $name ?? $this->name, $headers);
	}

	public function url()
	{
		return self::storage()->url($this->fullname);
	}

	public function entity()
	{
		return $this->entity ?: $this->entity = call_user_func([$this->entity_type, 'find'], $this->entity_id);
	}

	public function __toString()
	{
		return (string)$this->guid;
	}

}
