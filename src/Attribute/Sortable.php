<?php

namespace Stylemix\Listing\Attribute;

interface Sortable
{

	/**
	 * Apply sort criteria to elastic search request
	 *
	 * @param mixed                          $criteria Requested criteria
	 * @param \Illuminate\Support\Collection $sort     Elastic search sort criteria
	 * @param string                         $key      Requested key
	 */
	public function applySort($criteria, $sort, $key) : void;
}
