<?php

namespace Stylemix\Listing\Contracts;

interface Filterable
{

	/**
	 * Apply criteria to ES filter query
	 *
	 * @param mixed $criteria
	 *
	 * @param \Illuminate\Support\Collection $filter
	 */
	public function applyFilter($criteria, $filter);

}
