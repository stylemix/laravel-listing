<?php
namespace Stylemix\Listing\Attribute;

use Illuminate\Support\Arr;
use Stylemix\Listing\Facades\Elastic;

trait AppliesRangeQuery
{
	use AppliesTermQuery;

	/**
	 * Apply search criteria to elastic search filter query
	 *
	 * @param mixed $criteria
	 * @param string $fieldName
	 *
	 * @return \Elastica\Query\AbstractQuery
	 */
	public function createRangeQuery($criteria, $fieldName = null)
	{
		$fieldName = $fieldName ?? $this->filterableName ?? $this->name;

		if (is_array($criteria) && Arr::isAssoc($criteria)) {
			$range = array_filter(
				$criteria,
				function ($value) {
					return $value !== null;
				}
			);

			if (count($range)) {
				return Elastic::query()->range($fieldName, $range);
			}

			throw new \BadMethodCallException('Range query without any bounds: ' . json_encode($criteria));
		}

		return $this->createTermQuery($criteria, $fieldName);
	}

}
