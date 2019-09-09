<?php

namespace Stylemix\Listing\Attribute;

use Illuminate\Support\Arr;
use Stylemix\Listing\Contracts\Aggregateble;

/**
 * @property mixed $source Enum keywords source
 */
class Enum extends Keyword implements Aggregateble
{

	/**
	 * Enum constructor.
	 *
	 * @param string $name
	 * @param string $enumClass
	 */
	public function __construct($name, $enumClass = null)
	{
		parent::__construct($name);

		if ($enumClass) {
			$this->source = $enumClass::choices();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function elasticMapping($mapping)
	{
		parent::elasticMapping($mapping);
		$mapping[$this->name . '_text'] = ['type' => 'text'];
	}

	public function applyIndexData($data, $model)
	{
		$value = $data->get($this->name);
		$data[$this->name . '_text'] = $value ? Arr::get($this->source, $value) : null;
	}

	public function getSelectOptions()
	{
		$options = collect($this->source)->map(function ($label, $value) {
			return compact('label', 'value');
		});

		return $options->values()->all();
	}

	/**
	 * @inheritDoc
	 */
	public function applyAggregation($aggregations, $filter)
	{
		$aggregations->put($this->name, [
			'filter' => ['bool' => ['filter' => $filter->except($this->name)->values()->all()]],
			'aggs' => [
				'available' => [
					'terms' => [
						'field' => $this->name,
						'size' => $this->aggregationSize ?: 60
					],
					'aggs' => [
						'entities' => [
							'top_hits' => [
								'_source' => [
									$this->name,
									$this->name . '_text',
								],
								'size' => 1,
							]
						],
					],
				],
			],
		]);
	}

	/**
	 * @inheritDoc
	 */
	public function collectAggregations($aggregations, $raw)
	{
		$entries = [];

		foreach (data_get($raw, $this->name . '.available.buckets', []) as $bucket) {
			$source = data_get($bucket, 'entities.hits.hits.0._source');
			if (empty($source)) {
				continue;
			}

			$entries[] = [
				'label' => $source[$this->name . '_text'],
				'value' => $source[$this->name],
				'count' => $bucket['doc_count'],
			];
		}

		$aggregations->put($this->name, $entries);
	}
}
