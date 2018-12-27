<?php

namespace Stylemix\Listing\Attribute;

use Stylemix\Listing\Contracts\Filterable;
use Stylemix\Listing\Contracts\Sortable;

class Date extends Base implements Sortable, Filterable
{

	use AppliesNumericQuery, AppliesDefaultSort;

	/**
	 * Adds attribute mappings for elastic search
	 *
	 * @param \Illuminate\Support\Collection $mapping Mapping to modify
	 *
	 * @return void
	 */
	public function elasticMapping($mapping)
	{
		$mapping[$this->name] = ['type' => 'date'];
	}

	/**
	 * Adds attribute casts
	 *
	 * @param \Illuminate\Support\Collection $casts
	 */
	public function applyCasts($casts)
	{
		$casts->put($this->name, 'datetime');
	}
}
