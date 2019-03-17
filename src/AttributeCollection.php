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
	 * All key variations (name, fills, sorts)
	 *
	 * @return \Stylemix\Listing\AttributeCollection
	 */
	public function allKeys()
	{
		return $this->keys()
			->merge($this->map->fills()->flatten())
			->merge($this->map->sorts()->flatten())
			->unique();
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
		return $this->keyByAll()->get($key);
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
		return $this->whereInstanceOf(Filterable::class);
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
			foreach ($attribute->sorts() as $key) {
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
