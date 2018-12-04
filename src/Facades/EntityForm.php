<?php

namespace Stylemix\Listing\Facades;

use Illuminate\Support\Facades\Facade;
use Stylemix\Listing\Form;

/**
 * @method static register(string $class, $builder) Register a fields builder function for attribute class
 * @method static extend(string $attribute, callable $callback) Add function that alters field by attribute name
 * @method static extendAll(callable $callback) Add function that performs some modifications to all fields
 * @method static array forAttributes($attributes) Generate form fields for given attributes
 */
class EntityForm extends Facade
{

	protected static function getFacadeAccessor()
	{
		return Form::class;
	}
}
