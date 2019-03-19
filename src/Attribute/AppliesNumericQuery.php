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
		$filterField = $this->filterField ?? $this->name;

		if (is_array($criteria) && Arr::isAssoc($criteria)) {
			$range = array_filter(
				(array) $criteria + ['gt' => null, 'gte' => null, 'lt' => null, 'lte' => null],
				function ($value) {
					return $value !== null;
				}
			);

			if (count($range)) {
				$range = array_map($this->integer ? 'intval' : 'floatval', $range);
				$filter[$this->name] = ['range' => [$filterField => $range]];
			}

			return;
		}

		$criteria = array_map($this->integer ? 'intval' : 'floatval', array_wrap($criteria));
		$filter[$this->name] = ['terms' => [$filterField => $criteria]];
	}

}
