<?php

namespace Stylemix\Listing;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Stylemix\Listing\Contracts\Aggregateble;
use Stylemix\Listing\Attribute\Base;
use Stylemix\Listing\Contracts\Filterable;
use Stylemix\Listing\Contracts\Searchable;
use Stylemix\Listing\Contracts\Sortable;

class AttributeCollection extends Collection
{

	/**
	 * Attribute all keys cache
	 *
	 * @var array
	 */
	protected $allKeysCache = null;

	/**
	 * Attribute instances by all supported keys
	 *
	 * @var array
	 */
	protected $findCache = null;

	/**
	 * All key variations (name, fills, sorts)
	 *
	 * @return array
	 */
	public function allKeys()
	{
		if (!is_null($this->allKeysCache)) {
			return $this->allKeysCache;
		}

		return $this->allKeysCache = $this->reduce(function ($keys, Base $attribute) {
			return array_unique(array_merge($keys, [$attribute->name], Arr::wrap($attribute->fills()), $attribute->filterKeys(), $attribute->sortKeys()));
		}, []);
	}

	/**
	 * Get attributes keyed by fills
	 *
	 * @return \Stylemix\Listing\AttributeCollection
	 */
	public function keyByFills()
	{
		$byFills = new static();

		$this->each(function (Base $attribute) use ($byFills) {
			foreach (Arr::wrap($attribute->fills()) as $key) {
				$byFills->put($key, $attribute);
			}
		});

		return $byFills;
	}

	/**
	 * Attributes mapped with all key variations (name, fills, sorts)
	 *
	 * @return \Stylemix\Listing\AttributeCollection
	 */
	public function keyByAll()
	{
		return $this
			->merge($this->keyByFills())
			->merge($this->implementsSortable());
	}

	/**
	 * Find attribute by all key variations (name, fills, sorts)
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public function find($key)
	{
		if (is_null($this->findCache)) {
			$this->findCache = $this->keyByAll()->all();
		}

		return isset($this->findCache[$key]) ? $this->findCache[$key] : null;
	}

	/**
	 * Get attributes that can be filled
	 *
	 * @return \Stylemix\Listing\AttributeCollection|Base[]
	 */
	public function fillable()
	{
		return $this->where('fillable', '=', true);
	}

	/**
	 * @return \Stylemix\Listing\AttributeCollection|Filterable[]|Base[]
	 */
	public function implementsFiltering()
	{
		$result = $this->make();

		$this->whereInstanceOf(Filterable::class)->each(function ($attribute) use ($result) {
			/** @var \Stylemix\Listing\Contracts\Filterable $attribute */
			foreach ($attribute->filterKeys() as $key) {
				$result->put($key, $attribute);
			}
		});

		return $result;
	}

	/**
	 * Get attributes that implement aggregations
	 *
	 * @return \Stylemix\Listing\AttributeCollection|Aggregateble[]|Base[]
	 */
	public function implementsAggregations()
	{
		return $this->whereInstanceOf(Aggregateble::class);
	}

	/**
	 * Get attributes that implement sorting keyed by sorting attributes
	 *
	 * @return \Stylemix\Listing\AttributeCollection|Sortable[]|Base[]
	 */
	public function implementsSortable()
	{
		$result = $this->make();

		$this->whereInstanceOf(Sortable::class)->each(function ($attribute) use ($result) {
			/** @var \Stylemix\Listing\Contracts\Sortable $attribute */
			foreach ($attribute->sortKeys() as $key) {
				$result->put($key, $attribute);
			}
		});

		return $result;
	}

	/**
	 * Get attributes that implement searching
	 *
	 * @return \Stylemix\Listing\AttributeCollection|Sortable[]|Base[]
	 */
	public function implementsSearching()
	{
		return $this->whereInstanceOf(Searchable::class);
	}
}
