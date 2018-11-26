<?php

namespace Stylemix\Listing\Attribute;

interface Aggregateble
{

	/**
	 * Apply aggregation to elastic search query
	 *
	 * @param \Illuminate\Support\Collection $aggregations
	 * @param \Illuminate\Support\Collection $filter
	 */
	public function applyAggregation($aggregations, $filter);

	/**
	 * Collect aggregations from raw ES result
	 *
	 * @param \Stylemix\Listing\Elastic\Aggregations $aggregations Collected aggregations
	 * @param array $raw Raw aggregation data from ES
	 */
	public function collectAggregations($aggregations, $raw);

}
