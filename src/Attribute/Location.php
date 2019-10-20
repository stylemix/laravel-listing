<?php

namespace Stylemix\Listing\Attribute;

use Elastica\Query\AbstractQuery;
use Illuminate\Support\Arr;
use Stylemix\Listing\Contracts\Filterable;
use Stylemix\Listing\Contracts\Sortable;
use Stylemix\Listing\Elastic\Query\Sort;
use Stylemix\Listing\Elastic\Query\SortGeoDistance;
use Stylemix\Listing\Facades\Elastic;

class Location extends Base implements Filterable, Sortable
{

	/**
	 * @inheritdoc
	 */
	public function __construct(string $name = null)
	{
		$name = $name ?? 'location';
		parent::__construct($name);
	}

	/**
	 * @inheritdoc
	 */
	public function elasticMapping($mapping)
	{
		$mapping[$this->name] = [
			'properties' => [
				'latlng' => ['type' => 'geo_point'],
				'zoom' => ['type' => 'integer'],
				'address' => ['type' => 'keyword'],
				'city' => ['type' => 'keyword'],
				'zip' => ['type' => 'keyword'],
				'region' => ['type' => 'keyword'],
				'country' => ['type' => 'keyword'],
			],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function applyFilter($criteria, $key) : AbstractQuery
	{
		return Elastic::query()->geo_distance($this->name . '.latlng', $criteria['latlng'], $criteria['distance']);
	}

	/**
	 * @inheritdoc
	 */
	public function applySort($criteria, $key) : Sort
	{
		if (is_string($criteria) && strpos($criteria, '|') !== false) {
			$criteria = explode('|', $criteria);
		}

		$criteria = Arr::wrap($criteria);
		$criteria += [
			1 => 'asc',
			2 => 'm'
		];

		return (new SortGeoDistance($this->name, $this->name . '.latlng', $criteria[0]))
			->setOrder($criteria[1])
			->setUnit($criteria[2])
			->setDistanceType('arc');
	}
}
