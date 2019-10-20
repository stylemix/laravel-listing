<?php

namespace Stylemix\Listing\Contracts;

use Stylemix\Listing\Elastic\Query\Sort;

interface Sortable
{

	/**
	 * List of keys available for sorting
	 *
	 * @return array
	 */
	public function sortKeys() : array;

	/**
	 * Apply sort criteria to elastic search request
	 *
	 * @param mixed $criteria Requested criteria
	 * @param string $key Requested key
	 *
	 * @return mixed
	 */
	public function applySort($criteria, $key) : Sort;
}
