<?php

namespace Gsdk\GuidStorage;

use Gsdk\GuidStorage\Facade\GuidStorageFacade;

class File {

	private $entity;

	private $model;

	protected function __construct(
		private readonly int      $id,
		protected readonly string $guid,
		protected readonly int    $entityId,
		protected readonly string $entityType,
		protected ?string         $name = null,
	) {

	}

	public static function fromModel(Eloquent\Model $model): ?static {
		if (!$model)
			return null;

		if ($model->type !== static::class)
			throw new \Exception('Model file type invalid, [' . static::class . '] expected');

		$file = new $model->type(
			$model->id,
			$model->guid,
			$model->entity_id,
			$model->entity_type,
			$model->name,
		);
		$file->model = $model;

		return $file;
	}

	public static function query() {
		return Eloquent\Model::whereType(static::class);
	}

	public static function createFromEntity($entity, string $name = null): ?static {
		if (!$entity->id)
			throw new \Exception('Entity empty');

		$model = Eloquent\Model::create([
			'guid' => static::generateGuid(),
			'entity_id' => $entity->id,
			'entity_type' => $entity::class,
			'type' => static::class,
			'name' => $name,
		]);

		$file = static::fromModel($model);

		$file->updateModel();

		return $file;
	}

	public static function findById($id): ?static {
		return static::fromModel(Eloquent\Model::where('id', $id)->first());
	}

	public static function findByGuid($guid): ?static {
		return static::fromModel(Eloquent\Model::where('guid', $guid)->first());
	}

	public static function findByEntity($entity): ?static {
		$query = Eloquent\Model::whereEntity($entity)
			->whereType(static::class);

		return static::fromModel($query->first());
	}

	public function __get($name) {
		if (in_array($name, []))
			return $this->$name;

		return null;
	}

	public function path(): string {
		return GuidStorageFacade::path($this->guid);
	}

	public function type(): string {
		return static::class;
	}

	public function exists(): bool {
		return file_exists($this->path());
	}

	public function hash($algorithm = 'md5') {
		return hash_file($algorithm, $this->path());
	}

	public function name(): string {
		return $this->name ?? (string)pathinfo($this->path(), PATHINFO_FILENAME);
		//$this->guid . '.' . Util\Mime::mimeToExtension($this->mime_type)
	}

	public function basename(): string {
		return pathinfo($this->path(), PATHINFO_BASENAME);
	}

	public function dirname(): string {
		return pathinfo($this->path(), PATHINFO_DIRNAME);
	}

	public function mimeType(): string {
		return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->path());
	}

	public function size(): int {
		return filesize($this->path());
	}

	public function lastModified(): int {
		return filemtime($this->path());
	}

	public function chmod($mode) {
		chmod($this->path(), $mode);
	}

	public function get($lock = false): ?string {
		return $this->exists() ? file_get_contents($this->path()) : null;
	}

	public function put($contents, $lock = false) {
		file_put_contents($this->path(), $contents, $lock ? LOCK_EX : 0);

		$this->updateModel();
	}

	public function delete() {
		//transaction
		$this->model()->delete();

		if ($this->exists())
			unlink($this->path());
	}

	public function model() {
		return $this->model ?? ($this->model = Eloquent\Model::findByGuid($this->guid));
	}

	public function entity() {
		return $this->entity ?? ($this->entity = call_user_func([$this->entityType, 'find'], $this->entityId));
	}

	public function isEntity($entity): bool {
		return $entity->id === $this->entityId && $entity::class === $this->entityType;
	}

	public function updateModel() {
		if ($this->exists())
			$this->model()->update([
				'mime_type' => $this->mimeType(),
				'size' => $this->size(),
				'mtime' => $this->lastModified(),
			]);
		else
			$this->model()->update([
				'mime_type' => null,
				'size' => 0,
				'mtime' => null,
			]);
	}

	public function __toString() {
		return $this->guid;
	}

	private static function generateGuid(): string {
		do {
			$guid = md5(uniqid());
		} while (Eloquent\Model::where('guid', $guid)->exists());

		return $guid;
	}

}
