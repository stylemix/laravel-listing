<?php

namespace Stylemix\Listing\Attribute;

use Stylemix\Listing\Contracts\Searchable;
use Stylemix\Listing\Contracts\Sortable;
use Stylemix\Listing\Elastic\Query\Sort;

class Text extends Base implements Sortable, Searchable
{
	/**
	 * Adds attribute mappings for elastic search
	 *
	 * @param \Illuminate\Support\Collection $mapping Mapping to modify
	 */
	public function elasticMapping($mapping)
	{
		$mapping[$this->name] = [
			'type' => 'text',
			'fields' => [
				'raw' => ['type' => 'keyword'],
			],
		];
	}

	/**
	 * Adds attribute casts
	 *
	 * @param \Illuminate\Support\Collection $casts
	 */
	public function applyCasts($casts)
	{
		$casts->put($this->name, 'string');
	}

	/**
	 * @inheritdoc
	 */
	public function applySort($criteria, $key) : Sort
	{
		return new Sort($this->name, $this->name . '.raw', $criteria);
	}

}
