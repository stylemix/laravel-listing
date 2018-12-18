<?php

namespace Stylemix\Listing;

use Illuminate\Support\Collection;
use Stylemix\Listing\Attribute\Aggregateble;
use Stylemix\Listing\Attribute\Base;
use Stylemix\Listing\Attribute\Filterable;
use Stylemix\Listing\Attribute\Searchable;
use Stylemix\Listing\Attribute\Sortable;

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
	 * Attributes mapped with all key variations (name, fills, sorts)
	 *
	 * @return \Stylemix\Listing\AttributeCollection
	 */
	public function keyByAll()
	{
		return $this->merge($this->keyBy->fills())
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
