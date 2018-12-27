<?php

namespace Stylemix\Listing\Attribute;

use Stylemix\Listing\Contracts\Filterable;
use Stylemix\Listing\Contracts\Sortable;

/**
 * @property boolean $integer
 * @method  $this integer() Accept only integer numbers
 */
class Numeric extends Base implements Filterable, Sortable
{

	use AppliesNumericQuery, AppliesDefaultSort;

	/**
	 * Adds attribute mappings for elastic search
	 *
	 * @param \Illuminate\Support\Collection $mapping Mapping to modify
	 */
	public function elasticMapping($mapping)
	{
		$mapping[$this->name] = ['type' => $this->integer ? 'integer' : 'float'];
	}

	/**
	 * Adds attribute casts
	 *
	 * @param \Illuminate\Support\Collection $casts
	 */
	public function applyCasts($casts)
	{
		$casts->put($this->name, $this->integer ? 'integer' : 'float');
	}

	public function isValueEmpty($value)
	{
		return trim($value) === '';
	}
}
