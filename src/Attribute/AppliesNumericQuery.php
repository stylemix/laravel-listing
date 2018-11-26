<?php
namespace Stylemix\Listing\Attribute;

use Illuminate\Support\Arr;

trait AppliesNumericQuery
{

	/**
	 * Apply search criteria to elastic search filter query
	 *
	 * @param mixed $criteria
	 * @param \Illuminate\Support\Collection $filter
	 */
	public function applyFilter($criteria, $filter)
	{
		if (is_array($criteria) && Arr::isAssoc($criteria)) {
			$range = array_filter((array) $criteria + ['gt' => null, 'gte' => null, 'lt' => null, 'lte' => null]);

			if (count($range)) {
				$filter[$this->name] = ['range' => [$this->name => $range]];
			}

			return;
		}

		$filter[$this->name] = ['terms' => [$this->name => array_wrap($criteria)]];
	}

}
