<?php

namespace Stylemix\Listing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class EntityData extends Model
{

	public $timestamps = false;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var  array
	 */
	protected $fillable = [
		'lang',
		'name',
		'value',
	];

	abstract public function entity() : BelongsTo;

	public function setValueAttribute($value)
	{
		$this->attributes['value'] = is_scalar($value) || is_null($value) ? $value : json_encode($value);
	}

	public function getValueAttribute()
	{
		$value = $this->getAttributeFromArray('value');

		if (!$value) {
			return $value;
		}

		if (str_is('{*}', $value) || str_is('[*]', $value)) {
			$value = json_decode($value);
		}

		return $value;
	}

}
