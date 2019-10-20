<?php

namespace Stylemix\Listing\Attribute;

use Stylemix\Listing\Facades\Elastic;

trait AppliesTermQuery
{

	/**
	 * Get ES term query
	 *
	 * @param $criteria
	 * @param string $name
	 *
	 * @return \Elastica\Query\Term|\Elastica\Query\Terms
	 */
	protected function createTermQuery($criteria, string $name)
	{
		if (is_array($criteria) && count($criteria) == 1) {
			$criteria = reset($criteria);
		}

		return is_array($criteria)
			? Elastic::query()->terms($name, array_values($criteria))
			: Elastic::query()->term([$name => $criteria]);
	}
}
