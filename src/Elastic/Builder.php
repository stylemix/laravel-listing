<?php

namespace Stylemix\Listing\Elastic;

use BadMethodCallException;
use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;
use Stylemix\Listing\Attribute\Base;
use Stylemix\Listing\AttributeCollection;
use Stylemix\Listing\Contracts\Filterable;
use Stylemix\Listing\Elastic\Query\Sort;
use Stylemix\Listing\Entity;
use Stylemix\Listing\Facades\Elastic;

class Builder
{

	/**
	 * @var \Elastica\Query
	 */
	protected $rootQuery;

	/**
	 * @var \Elastica\Query\AbstractQuery|\Elastica\Query\BoolQuery
	 */
	protected $filterQuery;

	/**
	 * @var \Elastica\Query\AbstractQuery|\Elastica\Query\BoolQuery
	 */
	protected $postFilterQuery;

	/**
	 * @var \Illuminate\Support\Collection
	 */
	protected $filterQueries;

	/**
	 * @var \Elastica\Query\FunctionScore
	 */
	protected $functionScore;

	protected $request = [];

	/**
	 * @var int Requesting page number
	 */
	protected $page = 1;

	protected $perPage = 15;

	/**
	 * The entity model being queried.
	 *
	 * @var \Stylemix\Listing\Entity
	 */
	protected $entity;

	/** @var \Stylemix\Listing\AttributeCollection Attributes for the entity */
	protected $attributes;

	/** @var callable Callback for request body */
	protected $buildWith;

	/**
	 * QueryBuilder constructor.
	 */
	public function __construct()
	{
		$this->rootQuery = new Query();
		$this->rootQuery->setParams($this->request);
		$this->rootQuery->setSize($this->perPage);
		$this->filterQueries = collect();
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
		$filter = $this->resolveFilterableAttribute($attribute)->applyFilter($criteria, $attribute);
		$this->filterQueries[$attribute] = $filter;

		if (!$this->postFilterQuery) {
			$this->postFilterQuery = $filter;
		}
		else {
			if (!$this->postFilterQuery instanceof Query\BoolQuery) {
				$boolQuery = Elastic::query()->bool();
				$boolQuery->addFilter($this->postFilterQuery);
				$this->postFilterQuery = $boolQuery;
			}

			$this->postFilterQuery->addFilter($filter);
		}

		$this->rootQuery->setPostFilter($this->postFilterQuery);

		return $this;
	}

	/**
	 * Add query filter criteria
	 *
	 * @param string|callable|\Elastica\Query\AbstractQuery $attribute Attribute name, query statement or callback
	 * @param mixed  $criteria  Search criteria to apply
	 * @param bool   $negative  Apply not negative filter (must_not)
	 *
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	public function where($attribute, $criteria = null, $negative = false)
	{
		// Allow developers to chain statements with callback
		if (is_callable($attribute)) {
			$attribute($this);

			return $this;
		}

		// Support for passing query object
		if ($attribute instanceof Query\AbstractQuery) {
			$filter = $attribute;
		}
		else {
			if (is_null($criteria)) {
				$filter = Elastic::query()->exists($attribute);
				$negative = !$negative;
			}
			else {
				$filter = $this->resolveFilterableAttribute($attribute)->applyFilter($criteria, $attribute);
			}
		}

		$this->addFilterQuery($filter, $negative);

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
	 * Add match filter by keyword
	 *
	 * @param string $keyword
	 * @param string $type
	 *
	 * @return $this
	 */
	public function match(string $keyword, $type = Query\MultiMatch::TYPE_CROSS_FIELDS)
	{
		$query = (new Query\MultiMatch())
			->setQuery($keyword)
			->setType($type);

		if (count($fields = $this->getSearchFields())) {
			$query->setFields($fields);
		}
		else {
			throw new \RuntimeException('No attributes are available for match query');
		}

		$this->addFilterQuery($query);

		return $this;
	}

	/**
	 * Add query search
	 *
	 * @param string $keyword
	 *
	 * @return $this
	 */
	public function queryString(string $keyword)
	{
		$filter = new Query\QueryString('*' . $keyword . '*');
		if (count($fields = $this->getSearchFields())) {
			$filter->setFields($fields);
		}
		else {
			throw new \RuntimeException('No attributes are available for querying by string');
		}

		$this->addFilterQuery($filter);

		return $this;
	}

	/**
	 * @param int $page
	 *
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	public function page(int $page)
	{
		$this->page = $page;
		$this->rootQuery->setFrom($this->perPage * ($page - 1));

		return $this;
	}

	/**
	 * Set page size for pagination
	 *
	 * @param int $perPage
	 *
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	public function perPage(int $perPage)
	{
		$this->perPage = $perPage;
		$this->rootQuery->setSize($perPage);

		if ($this->page > 1) {
			$this->page($this->page);
		}

		return $this;
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

		$aggregatebles = $this->attributes->implementsAggregations();
		/** @var \Stylemix\Listing\Contracts\Aggregateble $definition */
		if (!($definition = $aggregatebles->get($attribute))) {
			throw new BadMethodCallException(
				sprintf('Attribute [%s] is not defined for aggregating in entity %s', $attribute, get_class($this->entity))
			);
		}

		$postFilters = $this->filterQueries->except($attribute)->values()->all();
		$agg = Elastic::aggregation()
			->filter(
				$attribute,
				Elastic::query()->bool()->setParam('filter', $postFilters)
			);

		foreach (Arr::wrap($definition->applyAggregation()) as $subAgg) {
			$agg->addAggregation($subAgg);
		}

		$this->rootQuery->addAggregation($agg);

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

		foreach (Arr::wrap($aggregations) as $attribute => $config) {
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
	 * @param mixed $direction
	 * @param bool $prepend
	 *
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	public function sort($sorts, $direction = 'asc', $prepend = false)
	{
		$sortables = $this->attributes->implementsSortable();
		$append = [];
		if (!is_array($sorts)) {
			$sorts = [$sorts => $direction];
		}

		foreach ($sorts as $key => $criteria) {
			if ($key == '_score') {
				$append[] = $key;
				continue;
			}

			/** @var $attribute \Stylemix\Listing\Contracts\Sortable */
			if (!$attribute = $sortables->get($key)) {
				throw new BadMethodCallException(
					sprintf('Attribute [%s] is not defined for sorting in entity %s', $key, get_class($this->entity))
				);
			}

			$append[] = $attribute->applySort($criteria, $key);
		}

		$sorts = $this->rootQuery->hasParam('sort') ? $this->rootQuery->getParam('sort') : [];
		if ($prepend) {
			$sorts = array_merge($append, $sorts);
		}
		else {
			$sorts = array_merge($sorts, $append);
		}

		$this->rootQuery->setSort($sorts);

		return $this;
	}

	/**
	 * Set or unset random order
	 *
	 * @param $seed
	 *
	 * @return Builder
	 */
	public function random($seed)
	{
		if (!$this->functionScore) {
			$this->rootQuery->setQuery(
				$this->functionScore = Elastic::query()->function_score()
			);
		}

		$this->functionScore->addRandomScoreFunction($seed, $this->filterQuery);

		return $this;
	}

	/**
	 * Set ES source parameter
	 *
	 * @param array $source
	 *
	 * @return \Stylemix\Listing\Elastic\Builder
	 */
	public function source(array $source)
	{
		$this->rootQuery->setSource($source);

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
			$this->match($array->search);
		}

		if ($array->query) {
			$this->queryString($array->query);
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
			$this->sort(Arr::wrap($array->sort));
		}

		if ($array->page) {
			$this->page($array->page);
		}

		if ($array->per_page) {
			$this->perPage($array->per_page);
		}

		if ($array->source) {
			$this->source(Arr::wrap($array->source));
		}
	}

	/**
	 * Build elastic search search request
	 *
	 * @return array
	 */
	public function build()
	{
		$array = $this->rootQuery->toArray();

		if (is_callable($buildWith = $this->buildWith)) {
			$array = $buildWith($array);
		}

		return $array;
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
		/** @var \Stylemix\Listing\Entity $model */
		$model = $this->getEntity();

		$result = $model->getElasticSearchClient()->search(array_merge([
			'index' => $model->getIndexName(),
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

		return $model::hydrateElasticquentResult($items, $meta = $result);
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
		$instance = $this->getEntity();
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
		$results = $this->perPage($size)->get(array_merge([
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
		return $this->perPage(1)
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
		$sorts = $this->rootQuery->getParam('sort');
		if (!is_array($sorts)) {
			return [];
		}

		$map = [];
		foreach ($sorts as $i => $sort) {
			if ($sort instanceof Sort) {
				$map[$sort->getKey()] = $i;
			}
		}

		return $map;
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
	 * Get current query component
	 *
	 * @return \Elastica\Query
	 */
	public function getQuery() : Query
	{
		return $this->rootQuery;
	}

	/**
	 * Get the model instance being queried.
	 *
	 * @return \Stylemix\Listing\Entity
	 */
	public function getEntity() : Entity
	{
		return $this->entity;
	}

	/**
	 * Set a model instance for the model being queried.
	 *
	 * @param \Stylemix\Listing\Entity $entity
	 *
	 * @return $this
	 */
	public function setEntity(Entity $entity)
	{
		$this->entity = $entity;
		$this->setAttributes($entity::getAttributeDefinitions());

		return $this;
	}

	/**
	 * @param \Stylemix\Listing\AttributeCollection $attributes
	 *
	 * @return $this
	 */
	public function setAttributes(AttributeCollection $attributes)
	{
		$this->attributes = $attributes;

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
	 * Resolve attribute instance from entity definitions
	 *
	 * @param string $attribute
	 *
	 * @return \Stylemix\Listing\Contracts\Filterable
	 */
	protected function resolveFilterableAttribute($attribute) : Filterable
	{
		/** @var \Stylemix\Listing\Contracts\Filterable $definition */
		if (!($definition = $this->attributes->implementsFiltering()->get($attribute))) {
			throw new BadMethodCallException(
				sprintf('Attribute [%s] is not defined for filtering in entity %s', $attribute, get_class($this->entity))
			);
		}

		return $definition;
	}

	/** Deprecated methods **/

	/**
	 * @param $keyword
	 * @param array $args
	 *
	 * @return $this
	 * @see match()
	 * @deprecated
	 */
	public function search($keyword, ...$args)
	{
		return $this->match($keyword, ...$args);
	}

	/**
	 * @param $keyword
	 *
	 * @return $this
	 * @see queryString()
	 * @deprecated
	 */
	public function query($keyword)
	{
		return $this->queryString($keyword);
	}

	/**
	 * @param array $source
	 *
	 * @return $this
	 * @deprecated
	 * @see queryString()
	 */
	public function setSource(array $source)
	{
		return $this->source($source);
	}

	/**
	 * @param int $perPage
	 *
	 * @return $this
	 * @deprecated
	 * @see perPage()
	 */
	public function setPerPage(int $perPage)
	{
		return $this->perPage($perPage);
	}

	/**
	 * @param int $page
	 *
	 * @return $this
	 * @deprecated
	 * @see page()
	 */
	public function setPage(int $page)
	{
		return $this->page($page);
	}

	/**
	 * Sets or adds filter depending on whether filter query is present or not
	 *
	 * @param \Elastica\Query\AbstractQuery $filter
	 * @param bool $negative
	 */
	protected function addFilterQuery(AbstractQuery $filter, $negative = false): void
	{
		// Since random query wraps the main filterQuery into itself
		// we wont be able to append any filterQuery into random function
		// if it is already initialized and applied
		if (!$this->filterQuery && $this->rootQuery->hasParam('query')) {
			throw new BadMethodCallException('Filtering methods should be called before random()');
		}

		if (!$this->filterQuery && !$negative) {
			$this->filterQuery = $filter;

			if (!$this->rootQuery->hasParam('query')) {
				$this->rootQuery->setQuery($this->filterQuery);
			}
		}
		else {
			$this->ensureBoolQuery();

			if ($negative) {
				$this->filterQuery->addMustNot($filter);
			}
			else {
				$this->filterQuery->addFilter($filter);
			}
		}
	}

	/**
	 * Converts filterQuery to bool query if it not converted yet
	 */
	protected function ensureBoolQuery(): void
	{
		if (!$this->filterQuery instanceof Query\BoolQuery) {
			$boolQuery = Elastic::query()->bool();
			$boolQuery->addMust(Elastic::query()->match_all());
			if ($this->filterQuery) {
				$boolQuery->addFilter($this->filterQuery);
			}
			$this->filterQuery = $boolQuery;
			$this->rootQuery->setQuery($boolQuery);
		}
	}
}
