<?php

namespace Stylemix\Listing\Attribute;

use Elastica\Query\AbstractQuery;
use Stylemix\Listing\Contracts\Aggregateble;
use Stylemix\Listing\Contracts\Filterable;
use Stylemix\Listing\Contracts\Searchable;
use Stylemix\Listing\Contracts\Sortable;
use Stylemix\Listing\Facades\Elastic;

class Keyword extends Base implements Filterable, Sortable, Searchable, Aggregateble
{
	use AppliesTermQuery, AppliesDefaultSort;

	/**
	 * Adds attribute mappings for elastic search
	 *
	 * @param \Illuminate\Support\Collection $mapping Mapping to modify
	 */
	public function elasticMapping($mapping)
	{
		$mapping[$this->name] = ['type' => 'keyword'];
	}

	/**
	 * @inheritDoc
	 */
	public function applyFilter($criteria, $key) : AbstractQuery
	{
		return $this->createTermQuery($criteria, $this->name);
	}

	/**
	 * @inheritDoc
	 */
	public function applyAggregation()
	{
		return Elastic::aggregation()
			->terms('available')
			->setField($this->name);
	}

	/**
	 * @inheritDoc
	 */
	public function collectAggregations($aggregations, $raw)
	{
		$entries = [];

		foreach (data_get($raw, $this->name . '.available.buckets', []) as $bucket) {
			$entries[] = [
				'value' => $bucket['value'],
				'count' => $bucket['doc_count'],
			];
		}

		$aggregations->put($this->name, $entries);
	}

}
