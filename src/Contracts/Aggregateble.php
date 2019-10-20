<?php

namespace Stylemix\Listing\Contracts;

/**
 * @property integer $aggregationSize Size for aggregation
 */
interface Aggregateble
{

	/**
	 * Apply aggregation to elastic search query
	 *
	 * @return \Elastica\Aggregation\AbstractAggregation
	 */
	public function applyAggregation();

	/**
	 * Collect aggregations from raw ES result
	 *
	 * @param \Stylemix\Listing\Elastic\Aggregations $aggregations Collected aggregations
	 * @param array $raw Raw aggregation data from ES
	 */
	public function collectAggregations($aggregations, $raw);

}
