<?php

namespace Stylemix\Listing\Attribute;

class Boolean extends Base implements Filterable
{

	/**
	 * Adds attribute mappings for elastic search
	 *
	 * @param \Illuminate\Support\Collection $mapping Mapping to modify
	 */
	public function elasticMapping($mapping)
	{
		$mapping[$this->name] = ['type' => 'boolean'];
	}

	public function applyCasts($casts)
	{
		$casts[$this->name] = 'boolean';
	}

	public function applyFilter($criteria, $filter)
	{
		$filter[$this->name] = ['term' => [$this->name => (boolean) $criteria]];
	}

	/**
	 * @inheritdoc
	 */
	public function formField()
	{
		return \Stylemix\Base\Fields\Checkbox::make($this->fillableName)
			->required($this->required)
			->label($this->label);
	}
}
