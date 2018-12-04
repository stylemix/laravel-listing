<?php

namespace Stylemix\Listing\Attribute;

/**
 * @property boolean $integer
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
