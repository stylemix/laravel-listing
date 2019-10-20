<?php

namespace Stylemix\Listing\Contracts;

use Elastica\Query\AbstractQuery;

interface Filterable
{

	/**
	 * List of keys available for filtering
	 *
	 * @return array
	 */
	public function filterKeys() : array;

	/**
	 * Apply criteria to ES filter query
	 *
	 * @param mixed $criteria Filter criteria
	 * @param string $key Field key by which filter was applied
	 *
	 * @return \Elastica\Query\AbstractQuery
	 */
	public function applyFilter($criteria, $key) : AbstractQuery;

}
