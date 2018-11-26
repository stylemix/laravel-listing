<?php

namespace Stylemix\Listing\Attribute;

use Illuminate\Database\Eloquent\Builder;
use Stylemix\Listing\Entity;
use Stylemix\Listing\Facades\Entities;
use Stylemix\Listing\Fields\RelationField;

/**
 * @property string $related Related entity
 * @property array  $mapProperties Properties of related entity to use in mapping
 * @property integer  $aggregationSize Limit size of returned aggregation terms
 */
class Relation extends Base implements Filterable, Aggregateble
{

	protected $queryBuilder;

	protected $where = [];

	/** @var string Related model primary key name */
	protected $otherKey;

	public function __construct(string $name, $related = null, $foreignKey = null, $otherKey = 'id')
	{
		parent::__construct($name);
		$this->related = $related ?? $name;
		$this->fillableName = $foreignKey ?? $name . '_id';
		$this->otherKey = $otherKey;
	}

	/**
	 * @inheritdoc
	 */
	public function applyIndexData($data, $model)
	{
		if ($data->has($this->name) && !$model->isDirty($this->fillableName)) {
			return;
		}

		if (!($model_id = $data->get($this->fillableName))) {
			return;
		}

		// Always retrieve data as collection
		$model_id  = array_wrap($model_id);
		$results   = $this->getIndexedQueryBuilder()
			->where($this->otherKey, $model_id)
			->get()
			->map(function (Entity $item) {
				$item = $item->getAttributes();
				return (object) ($this->mapProperties ? array_only($item, $this->mapProperties) : $item);
			})
			->keyBy($this->otherKey);

		// Sort models by input ids
		$array = collect();
		foreach ($model_id as $id) {
			$array[] = $results->get($id);
		}

		$array = $array->filter();

		// then, if not multiple, just take the first item
		$ids = $array->pluck($this->otherKey);
		$data->put($this->fillableName, $this->multiple ? $ids->all() : $ids->first());
		$data->put($this->name, $this->multiple ? $array->all() : $array->first());
	}

	/**
	 * Adds attribute mappings for elastic search
	 *
	 * @param \Illuminate\Support\Collection $mapping Mapping to modify
	 *
	 * @return void
	 */
	public function elasticMapping($mapping)
	{
		$mapping[$this->fillableName] = ['type' => 'integer'];

		$modelMapping = $this->getInstance()->getMappingProperties();

		$mapping[$this->name] = [
			'type' => 'nested',
			'properties' => $this->mapProperties ? array_only($modelMapping, $this->mapProperties) : $modelMapping,
		];
	}

	/**
	 * Adds attribute casts
	 *
	 * @param \Illuminate\Support\Collection $casts
	 */
	public function applyCasts($casts)
	{
		$casts->put($this->fillableName, 'integer');
	}

	/**
	 * @inheritdoc
	 */
	public function formField()
	{
		return RelationField::make($this->fillableName)
			->required($this->required)
			->multiple($this->multiple)
			->label($this->label)
			->related($this->related)
			->otherKey($this->otherKey);
	}

	/**
	 * @inheritdoc
	 */
	public function isValueEmpty($value)
	{
		return trim($value) === '';
	}

	/**
	 * Apply search criteria to elastic search filter query
	 *
	 * @param mixed $criteria
	 *
	 * @param \Illuminate\Support\Collection $filter
	 */
	public function applyFilter($criteria, $filter)
	{
		$filter->put($this->name, ['terms' => [$this->fillableName => array_wrap($criteria)]]);
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
				'nested' => [
					'nested' => ['path' => $this->name],
					'aggs' => [
						'available' => [
							'terms' => [
								'field' => $this->name . '.id',
								'size' => $this->aggregationSize ?: 60
							],
							'aggs' => [
								'entities' => [
									'top_hits' => [
										'size' => 1,
									]
								],
							],
						]
					]
				]
			],
		]);
	}

	/**
	 * Collect aggregations from raw ES result
	 *
	 * @param \Stylemix\Listing\Elastic\Aggregations $aggregations
	 * @param array $raw Raw aggregation data from ES
	 */
	public function collectAggregations($aggregations, $raw)
	{
		$entries = [];

        foreach (data_get($raw, $this->name . '.nested.available.buckets', []) as $bucket) {
			$source = data_get($bucket, 'entities.hits.hits.0._source');
			if (empty($source)) {
				continue;
			}

			$entries[] = array_merge(
				$source,
				['count' => $bucket['doc_count']]
			);
        }

        $aggregations->put($this->name, $entries);
	}

	/**
	 * Adds constraint to model queries
	 *
	 * @param string $column
	 * @param string $operator
	 * @param mixed $value
	 * @param string $boolean
	 *
	 * @return static
	 */
	public function where($column, $operator = null, $value = null, $boolean = 'and')
	{
		$this->where[] = func_get_args();

		return $this;
	}

	/**
	 * Whether owner entity should be updated by related entity changes for this attribute
	 *
	 * @param Entity $owner Owner entity model of this attribute
	 * @param Entity $related Related entity model
	 *
	 * @return void|boolean
	 */
	public function shouldTriggerRelatedUpdate($owner, $related)
	{

	}

	/**
	 * Get new eloquent builder for related model
	 *
	 * @return Builder
	 */
	protected function getQueryBuilder()
	{
		$builder = $this->getInstance()->newQuery();

		foreach ($this->where as $criteria) {
			$builder->where(...$criteria);
		}

		return $builder;
	}

	/**
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	protected function getIndexedQueryBuilder()
	{
		$builder = $this->getInstance()->search();

		foreach ($this->where as $criteria) {
			$builder->where(...$criteria);
		}

		return $builder;
	}

	/**
	 * Creates new instance of related entity
	 *
	 * @return \Stylemix\Listing\Entity
	 */
	public function getInstance()
	{
		return Entities::make($this->related);
	}

	/**
	 * Get related entity's key field name
	 *
	 * @return string
	 */
	public function getOtherKey()
	{
		return $this->otherKey;
	}
}
