<?php

namespace Stylemix\Listing;

use Illuminate\Database\Eloquent\Model;

class EntityData extends Model
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
			$value = json_decode($value, true);
		}

		return $value;
	}

	public function newInstance($attributes = [], $exists = false)
	{
		$instance = parent::newInstance($attributes, $exists);

		// EntityData uses different tables depending on which entity it depends
		// $this instance has correct table name and it should pass it to new instantiated model
		$instance->setTable($this->getTable());

		return $instance;
	}

}
