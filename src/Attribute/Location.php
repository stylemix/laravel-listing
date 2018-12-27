<?php

namespace Stylemix\Listing\Attribute;

use Stylemix\Listing\Contracts\Filterable;
use Stylemix\Listing\Contracts\Sortable;

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
	public function applyFilter($criteria, $filter)
	{
		$filter[$this->name] = [
			'geo_distance' => [
				$this->name . '.latlng' => $criteria['latlng'],
				'distance' => $criteria['distance'],
			],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function applySort($criteria, $sort, $key) : void
	{
		if (is_string($criteria) && strpos($criteria, '|') !== false) {
			$criteria = explode('|', $criteria);
		}

		$criteria = array_wrap($criteria);
		$criteria += [
			1 => 'asc',
			2 => 'm'
		];

		$sort->put($key, [
			'_geo_distance' => [
				$this->name . '.latlng' => $criteria[0],
				'order' => $criteria[1],
				'unit' => $criteria[2],
				'mode' => 'min',
				'distance_type' => 'arc'
			],
		]);
	}
}
