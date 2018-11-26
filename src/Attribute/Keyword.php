<?php

namespace Stylemix\Listing\Attribute;

class Keyword extends Base implements Filterable, Sortable, Searchable
{

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
	 * Apply criteria to ES filter query
	 *
	 * @param mixed                          $criteria
	 * @param \Illuminate\Support\Collection $filter
	 */
	public function applyFilter($criteria, $filter)
	{
		$filter->put($this->name, ['terms' => [$this->fillableName => array_wrap($criteria)]]);
	}

	/**
	 * @inheritdoc
	 */
	public function applySort($criteria, $sort, $key): void
	{
		$sort->put($key, [
			$this->name => $criteria,
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function formField()
	{
		return \Stylemix\Base\Fields\Input::make($this->fillableName)
			->required($this->required)
			->multiple($this->multiple)
			->label($this->label);
	}
}
