<?php

namespace Stylemix\Listing\Attribute;

use Stylemix\Listing\Contracts\Filterable;

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

}
