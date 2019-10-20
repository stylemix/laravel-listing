<?php

namespace Stylemix\Listing\Attribute;

use Elastica\Query\AbstractQuery;
use Stylemix\Listing\Contracts\Aggregateble;
use Stylemix\Listing\Contracts\Filterable;
use Stylemix\Listing\Contracts\Sortable;
use Stylemix\Listing\Facades\Elastic;

/**
 * @property boolean $integer
 * @method  $this integer() Accept only integer numbers
 */
class Numeric extends Base implements Filterable, Sortable, Aggregateble
{
	use AppliesRangeQuery, AppliesDefaultSort;

	protected $aggregatedField = null;

	public function __construct(string $name)
	{
		parent::__construct($name);

		$this->aggregatedField = $name;
	}

	/**
	 * Adds attribute mappings for elastic search
	 *
	 * @param \Illuminate\Support\Collection $mapping Mapping to modify
	 */
	public function elasticMapping($mapping)
	{
		$mapping[$this->name] = ['type' => $this->integer ? 'integer' : 'float'];
	}

	/**
	 * Adds attribute casts
	 *
	 * @param \Illuminate\Support\Collection $casts
	 */
	public function applyCasts($casts)
	{
		$casts->put($this->name, $this->integer ? 'integer' : 'float');
	}

	public function isValueEmpty($value)
	{
		return trim($value) === '';
	}

	/**
	 * @inheritDoc
	 */
	public function applyFilter($criteria, $key) : AbstractQuery
	{
		if (is_array($criteria)) {
			$criteria = array_map($this->integer ? 'intval' : 'floatval', $criteria);
		}

		return $this->createRangeQuery($criteria);
	}

	/**
	 * Apply aggregation to elastic search query
	 */
	public function applyAggregation()
	{
		return [
			Elastic::aggregation()->min('min')->setField($this->aggregatedField),
			Elastic::aggregation()->max('max')->setField($this->aggregatedField),
		];
	}

	/**
	 * Collect aggregations from raw ES result
	 *
	 * @param \Stylemix\Listing\Elastic\Aggregations $aggregations
	 * @param array $raw
	 */
	public function collectAggregations($aggregations, $raw)
	{
		$aggregations->put($this->name, [
			'min' => data_get($raw, $this->name . '.min.value'),
			'max' => data_get($raw, $this->name . '.max.value'),
		]);
	}
}
