<?php

namespace Stylemix\Listing\Elastic;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Fluent;
use Stylemix\Listing\Attribute\Base;

class Builder
{
	protected $request = [];

	protected $page = 1;

	protected $perPage = 15;

	protected $where;

	protected $whereNot;

	protected $facet;

	protected $search;

	protected $query;

	protected $sort;

	protected $random;

	protected $aggregations;

	/** @var string Entity class name */
	protected $entityClass;

	/** @var \Stylemix\Listing\Entity Entity instance */
	protected $entity;

	/** @var \Stylemix\Listing\AttributeCollection Attributes for the entity */
	protected $attributes;

	/** @var array Source filtering */
	protected $source;

	/** @var callable Callback for request body */
	protected $buildWith;

	private $sortMap = [];

	/**
	 * QueryBuilder constructor.
	 *
	 * @param string $entityClass
	 */
	public function __construct($entityClass)
	{
		$this->where        = collect();
		$this->whereNot     = collect();
		$this->facet        = collect();
		$this->sort         = collect();
		$this->aggregations = collect();
		$this->entityClass  = $entityClass;
		$this->attributes   = $entityClass::getAttributeDefinitions();
		$this->entity       = new $entityClass;
	}

	/**
	 * Add facet filter criteria
	 *
	 * @param string $attribute Attribute name
	 * @param mixed  $criteria  Search criteria to apply
	 *
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	public function filter($attribute, $criteria)
	{
		if ($attribute == 'id') {
			return $this->where($attribute, $criteria);
		}

		/** @var \Stylemix\Listing\Contracts\Filterable $definition */
		if (!($definition = $this->attributes->implementsFiltering()->get($attribute))) {
			return $this;
		}

		$definition->applyFilter($criteria, $this->facet);

		return $this;
	}

	/**
	 * Add query filter criteria
	 *
	 * @param string|callable $attribute Attribute name
	 * @param mixed  $criteria  Search criteria to apply
	 * @param bool   $negative  Apply not negative filter (must_not)
	 *
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	public function where($attribute, $criteria = null, $negative = false)
	{
		$statements = collect();

		// Allow developers to push custom raw statements
		if (is_callable($attribute)) {
			$attribute($statements, $this);
		}
		elseif ($attribute == 'id') {
			if (is_string($criteria) && strpos($criteria, ',') !== false) {
				$criteria = explode(',', $criteria);
			}

			$statements['id'] = [
				'terms' => [
					'id' => array_map('intval', array_wrap($criteria)),
				],
			];
		}
		else {
			/** @var \Stylemix\Listing\Contracts\Filterable $definition */
			if (!($definition = $this->attributes->implementsFiltering()->get($attribute))) {
				return $this;
			}

			$definition->applyFilter($criteria, $statements);
		}

		if ($negative) {
			$this->whereNot = $this->whereNot->merge($statements->values());
		}
		else {
			$this->where = $this->where->merge($statements->values());
		}

		return $this;
	}


	/**
	 * Add negative query filter criteria
	 *
	 * @param string $attribute Attribute name
	 * @param mixed  $criteria  Criteria to apply
	 *
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	public function whereNot($attribute, $criteria)
	{
		return $this->where($attribute, $criteria, true);
	}

	/**
	 * Add attribute for aggregation
	 *
	 * @param string $attribute
	 * @param mixed $config
	 *
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	public function aggregate($attribute, $config = true)
	{
		if (!$config) {
			$this->aggregations->forget($attribute);
			return $this;
		}

		$this->aggregations[$attribute] = $config;

		return $this;
	}

	/**
	 * Add aggregations
	 *
	 * @param array $aggregations
	 *
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	public function aggregations($aggregations)
	{
		$this->aggregations = collect();

		foreach (array_wrap($aggregations) as $attribute => $config) {
			if (!$config) {
				continue;
			}

			$this->aggregate($attribute, $config);
		}

		return $this;
	}

	/**
	 * Add sort criteria
	 *
	 * @param mixed $sorts
	 *
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	public function sort($sorts)
	{
		$sortables = $this->attributes->implementsSortable();

		foreach ($sorts as $key => $criteria) {
			if (!$attribute = $sortables->get($key)) {
				continue;
			}

			$attribute->applySort($criteria, $this->sort, $key);
		}

		return $this;
	}

	/**
	 * Fill query from array
	 *
	 * @param array $array
	 */
	public function fromArray($array)
	{
		$array = new Fluent($array);

		if ($array->search) {
			$this->search($array->search);
		}

		if ($array->query) {
			$this->query($array->query);
		}

		if ($array->where) {
			foreach ((array) $array->where as $attribute => $criteria) {
				$this->where($attribute, $criteria);
			}
		}

		if ($array->not) {
			foreach ((array) $array->not as $attribute => $criteria) {
				$this->whereNot($attribute, $criteria);
			}
		}

		if ($array->filter) {
			foreach ((array) $array->filter as $attribute => $criteria) {
				$this->filter($attribute, $criteria);
			}
		}

		if ($array->aggregations) {
			$this->aggregations($array->aggregations);
		}

		if ($array->sort) {
			$this->sort(array_wrap($array->sort));
		}

		if ($array->page) {
			$this->setPage($array->page);
		}

		if ($array->per_page) {
			$this->setPerPage($array->per_page);
		}

		if ($array->source) {
			$this->setSource(array_wrap($array->source));
		}
	}

	/**
	 * Build elastic search search request
	 *
	 * @return array
	 */
	public function build()
	{
		$body = array_merge($this->request, [
			'size' => $this->perPage,
			'from' => ($this->page - 1) * $this->perPage,
			'track_scores' => true,
		]);

		if (is_array($this->source)) {
			$body['_source'] = $this->source;
		}

		if ($this->query && count($fields = $this->getSearchFields())) {
			$this->where[] = [
				'query_string' => [
					'query' => '*' . $this->query . '*',
					'fields' => $fields,
				],
			];
		}

		if ($this->search && count($fields = $this->getSearchFields())) {
			$this->facet['search'] = [
				'multi_match' => [
					'query' => $this->search,
					'type' => 'cross_fields',
					'fields' => $fields,
				],
			];
		}

		if ($this->where->isNotEmpty() || $this->whereNot->isNotEmpty()) {
			$query = array_get($body, 'query');
			$body['query'] = [
				'bool' => (object) array_filter([
					'filter' => $this->where->values()->merge(array_filter([$query]))->all(),
					'must_not' => $this->whereNot->values()->all()
				])
			];
		}

		if ($this->random) {
			$query = array_get($body, 'query');

			$body['query'] = [
				'function_score' => [
					'functions' => [['random_score' => (object) []]],
				]
			];

			if ($query) {
				$body['query']['function_score']['query'] = $query;
			}
		}

		if ($this->facet->isNotEmpty()) {
			$body['post_filter'] = [
				'bool' => [
					'filter' => $this->facet->values()->all()
				]
			];
		}

		if (!$this->random && $this->sort->isNotEmpty()) {
			$body['sort'] = [];
			$this->sort->each(function ($sort, $key) use (&$body) {
				$body['sort'][] = $sort;
				$this->sortMap[$key] = count($body['sort']) - 1;
			});
		}

		if ($this->aggregations->isNotEmpty()) {
			$aggregatebles = $this->attributes->implementsAggregations();
			$aggs = collect();

			foreach ($this->aggregations as $attribute => $config) {
				/** @var \Stylemix\Listing\Contracts\Aggregateble $definition */
				if (!($definition = $aggregatebles->get($attribute))) {
					continue;
				}

				$definition->applyAggregation($aggs, $this->facet);
			}

			$body['aggs'] = $aggs->all();
		}

		if (is_callable($buildWith = $this->buildWith)) {
			$body = $buildWith($body);
		}

		return $body;
	}

	/**
	 * Execute ES query
	 *
	 * @param array $params Override params passed to search query. 'body' is merged separately.
	 *
	 * @return \Stylemix\Listing\Elastic\Collection
	 */
	public function get($params = [])
	{
		/** @var \Stylemix\Listing\Entity $instance */
		$instance = new $this->entityClass;
		$result   = $instance->getElasticSearchClient()->search(array_merge([
			'index' => $instance->getIndexName(),
			'body' => $this->build(),
		], $params));

		$items = array_pull($result, 'hits.hits', []);

		// We need to map back sort values from indexed array to associative
		foreach ($items as &$hit) {
			if (!isset($hit['sort'])) {
				continue;
			}

			$sort = [];
			foreach ($this->getSortMap() as $key => $index) {
				$sort[$key] = $hit['sort'][$index];
			}

			$hit['sort'] = $sort;
		}

		$result['_per_page'] = $this->getPerPage();

		return $instance::hydrateElasticquentResult($items, $meta = $result);
	}

	/**
	 * Retrieve records by scroll ID
	 * @param string $scrollId
	 * @param string $scroll Scroll TTL
	 *
	 * @return \Stylemix\Listing\Elastic\Collection
	 */
	public function scroll($scrollId, $scroll = '1m')
	{
		/** @var \Stylemix\Listing\Entity $instance */
		$instance = new $this->entityClass;
		$result   = $instance->getElasticSearchClient()->scroll([
			'scroll' => $scroll,
			'scroll_id' => $scrollId,
		]);

		$items = array_pull($result, 'hits.hits', []);

		return $instance::hydrateElasticquentResult($items, $meta = $result);
	}

	/**
	 * Chunk the results of the query.
	 *
	 * @param int $size
	 * @param callable $callback
	 * @param array $params
	 *
	 * @return bool
	 */
	public function chunk($size, $callback, $params = [])
	{
		$page = 1;
		$results = $this->setPerPage($size)->get(array_merge([
			'scroll' => '1m',
		], $params));

		if (!$results->count()) {
			return true;
		}

		if ($callback($results, $page) === false) {
			return false;
		}

		$scrollId = $results->getScrollId();

		while (true) {
			$page ++;
			$results = $this->scroll($scrollId);

			if (!$results->count()) {
				break;
			}

			if ($callback($results, $page) === false) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get total hits count for the query
	 *
	 * @return int
	 */
	public function count()
	{
		return $this->setPerPage(1)
			->get()
			->totalHits();
	}

	/**
	 * Find model by key or multiple keys
	 *
	 * @param mixed $id
	 *
	 * @return \Stylemix\Listing\Entity|\Stylemix\Listing\Elastic\Collection|null
	 */
	public function find($id)
	{
		$result = $this->where($this->entity->getKeyName(), $id)->get();

		return is_array($id) || $id instanceof Arrayable ? $result : $result->first();
	}

	/**
	 * Find model by key or throw an exception
	 *
	 * @param mixed $id
	 *
	 * @return \Stylemix\Listing\Entity|null
	 */
	public function findOrFail($id)
	{
		$result = $this->find($id);

		if (is_array($id) || $id instanceof Arrayable) {
			if (count($result) === count(array_unique($id))) {
				return $result;
			}
		} elseif (! is_null($result)) {
			return $result;
		}

		throw (new ModelNotFoundException)->setModel(
			get_class($this->entity), $id
		);
	}

	/**
	 * Execute the query and get the first result or throw an exception.
	 *
	 * @return \Stylemix\Listing\Entity|null
	 *
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
	 */
	public function firstOrFail()
	{
		if (! is_null($model = $this->first())) {
			return $model;
		}

		throw (new ModelNotFoundException)->setModel(get_class($this->entity));
	}

	public function __call($method, $arguments)
	{
		return $this->get()->{$method}(...$arguments);
	}

	/**
	 * Make elastic search builder. Optionally fill builder with request.
	 *
	 * @param string $entityClass
	 * @param mixed  $request
	 *
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	public static function make($entityClass, $request = null)
	{
		$builder = new static($entityClass);

		if ($request instanceof Arrayable) {
			$request = $request->toArray();
		}

		if (is_array($request)) {
			$builder->fromArray($request);
		}

		return $builder;
	}

	/**
	 * Add keyword search
	 *
	 * @param $keyword
	 *
	 * @return $this
	 */
	public function search($keyword)
	{
		$this->search = $keyword;

		return $this;
	}

	/**
	 * Add query search
	 *
	 * @param $keyword
	 *
	 * @return $this
	 */
	public function query($keyword)
	{
		$this->query = $keyword;

		return $this;
	}

	/**
	 * @param int $page
	 *
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	public function setPage(int $page)
	{
		$this->page = $page;

		return $this;
	}

	/**
	 * @param int $perPage
	 *
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	public function setPerPage(int $perPage)
	{
		$this->perPage = $perPage;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getPerPage(): int
	{
		return $this->perPage;
	}

	/**
	 * @return array
	 */
	protected function getSortMap(): array
	{
		return $this->sortMap;
	}

	/**
	 * @param array $source
	 *
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	public function setSource(array $source)
	{
		$this->source = $source;

		return $this;
	}

	/**
	 * Set or unset random order
	 *
	 * @param boolean $random
	 *
	 * @return Builder
	 */
	public function random($random = true)
	{
		$this->random = $random;

		return $this;
	}

	/**
	 * Set raw ES request body
	 *
	 * @param array $request
	 *
	 * @return Builder
	 */
	public function setRequest(array $request)
	{
		$this->request = $request;

		return $this;
	}

	/**
	 * Set callback to process a request body after build
	 *
	 * @param callable $buildWith
	 *
	 * @return $this
	 */
	public function buildWith(callable $buildWith)
	{
		$this->buildWith = $buildWith;

		return $this;
	}

	/**
	 * Get list of fields to use in full text search
	 *
	 * @return array
	 */
	protected function getSearchFields() : array
	{
		return $this->attributes
			->implementsSearching()
			->map(function (Base $attribute) {
				$config = new Fluent($attribute->get('search', []));

				$name = $attribute->name;

				if ($config->boost) {
					$name .= '^' . $config->boost;
				}

				return $name;
			})
			->values()
			->all();
	}

	/**
	 * @return \Stylemix\Listing\Entity
	 */
	public function getEntity() : \Stylemix\Listing\Entity
	{
		return $this->entity;
	}

}
