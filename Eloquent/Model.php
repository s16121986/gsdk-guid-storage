<?php

namespace Gsdk\GuidStorage\Eloquent;

use Gsdk\GuidStorage\File;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Model extends BaseModel {

	const CREATED_AT = 'created';
	const UPDATED_AT = 'updated';

	protected $table = 's_files';

	public $timestamps = true;

	protected $fillable = [
		'guid',
		'name',
		'type',
		'entity_id',
		'entity_type',
		'mime_type',
		'size',
		'mtime',
		'index'
	];

	public static function scopeWhereEntity($query, $entity) {
		$query
			->where('entity_id', $entity->id)
			->where('entity_type', get_class($entity));
	}

	public static function scopeWhereType($query, $fileObject) {
		$query->where('type', is_string($fileObject) ? $fileObject : get_class($fileObject));
	}

	public static function scopeEntityColumn(Builder $builder, string $columnName) {
		$fileClass = static::class;
		$entity = $builder->getModel();
		/*$query = self::query()
			->whereColumn('s_files.entity_id', $entity->getTable() . '.id')
			->where('s_files.entity_type', get_class($entity))
			->where('s_files.type', $fileClass);

		*/
		$builder->addSelect(DB::raw('(SELECT guid FROM s_files'
			. ' WHERE entity_id=`' . $entity->getTable() . '`.id'
			. ' AND entity_type="' . addslashes(get_class($entity)) . '"'
			. ' AND type="' . addslashes($fileClass) . '"'
			. ' LIMIT 1) as `' . $columnName . '`'));
	}

	public function file(): ?File {
		return File::fromModel($this);
	}

}
