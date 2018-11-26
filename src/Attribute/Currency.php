<?php

namespace Stylemix\Listing\Attribute;

use Stylemix\Base\Fields\Number;

class Currency extends Base implements Filterable, Sortable, Aggregateble
{

	use AppliesNumericQuery;

	public $filterComponent = 'currency-filter';

	public $filterable = true;

	protected $aggregatedField = null;

	protected $sortField = null;

	public function __construct(string $name)
	{
		parent::__construct($name);
		$this->aggregatedField = $name;
		$this->sortField = $name;
	}

	/**
	 * Adds attribute casts
	 *
	 * @param \Illuminate\Support\Collection $casts
	 *
	 * @return void
	 */
	public function applyCasts($casts)
	{
		$casts->put($this->name, 'float');
	}

	/**
	 * @inheritdoc
	 */
	public function formField()
	{
		return Number::make($this->fillableName)
			->min(0)
			->multiple($this->multiple)
			->label($this->label);
	}

	/**
	 * Adds attribute mappings for elastic search
	 *
	 * @param \Illuminate\Support\Collection $mapping Mapping to modify
	 */
	public function elasticMapping($mapping)
	{
		$mapping[$this->name] = [
			'type' => 'scaled_float',
			"scaling_factor" => 2
		];
	}

	/**
	 * Apply aggregation to elastic search query
	 *
	 * @param \Illuminate\Support\Collection $aggregations
	 * @param \Illuminate\Support\Collection $filter
	 */
	public function applyAggregation($aggregations, $filter)
	{
		$aggregations->put($this->name, [
			'filter' => ['bool' => ['filter' => $filter->except($this->name)->values()->all()]],
			'aggs' => [
				'min' => ['min' => ['field' => $this->aggregatedField]],
				'max' => ['max' => ['field' => $this->aggregatedField]],
			],
		]);
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

	/**
	 * @inheritdoc
	 */
	public function applySort($criteria, $sort, $key) : void
	{
		$sort->put($key, [
			$this->sortField => $criteria,
		]);
	}
}
