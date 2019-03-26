<?php

namespace Stylemix\Listing;

class AttributeListener
{

	public function saving(Entity $model)
	{
		$attributes  = collect($model->getAttributes());
		$definitions = $model::getAttributeDefinitions();

		// Get defaults only for those keys that are missing
		$defaults = $definitions
			->diffKeys($attributes)
			->map->applyDefaultValue($attributes, $model)
			->filter(function ($value) {
				return $value !== null;
			});

		$attributes = $attributes->merge($defaults);

		// Pipe definitions through saving method
		$definitions->each->saving($attributes, $model);

		$model->setRawAttributes($attributes->all());
	}

	function saved(Entity $model)
	{
		$definitions = $model::getAttributeDefinitions();

		// Pipe definitions through saved method
		$definitions->each->saved($model);
	}

	function deleting(Entity $model)
	{
		$definitions = $model::getAttributeDefinitions();
		$definitions->each->deleting($model);
	}

}
