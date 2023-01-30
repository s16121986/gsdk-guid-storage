<?php

namespace Gsdk\GuidStorage\Eloquent;

use Gsdk\GuidStorage\File;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Support\Facades\DB;

class Model extends BaseModel
{
	protected $table = 's_files';

	public $timestamps = true;

	protected $fillable = [
		'guid',
		'name',
		'type',
		'parent_id',
		'index'
	];

	public function file(): File
	{
		return new $this->type($this->guid, $this->parent_id);
	}

	public static function createFromParent(string $fileType, int $parentId, ?string $name = null)
	{
		return static::create([
			'guid' => static::generateGuid(),
			'type' => $fileType,
			'parent_id' => $parentId,
			'name' => $name
		]);
	}

	public static function findByGuid($guid): ?static
	{
		return static::where('guid', $guid)->first();
	}

	public static function scopeWhereGuid($query, string $guid)
	{
		$query->where('guid', $guid);
	}

	public static function scopeWhereType($query, string $fileType)
	{
		$query->where('type', $fileType);
	}

	public static function scopeWhereParent($query, $parent)
	{
		$query->where('parent_id', is_object($parent) ? $parent->id : $parent);
	}

	public static function scopeParentColumn(Builder $builder, string $fileType, string $columnName)
	{
		$entity = $builder->getModel();
		$builder->addSelect(
			DB::raw(
				'(SELECT guid FROM s_files'
				. ' WHERE parent_id=`' . $entity->getTable() . '`.id'
				. ' AND type="' . addslashes($fileType) . '"'
				. ' LIMIT 1) as `' . $columnName . '`'
			)
		);
	}

	private static function generateGuid(): string
	{
		do {
			$guid = md5(uniqid());
		} while (static::whereGuid($guid)->exists());

		return $guid;
	}
}
