<?php

namespace Stylemix\Listing\Attribute;

class Location extends Base implements Filterable, Sortable
{

	/**
	 * Adds attribute mappings for elastic search
	 *
	 * @param \Illuminate\Support\Collection $mapping Mapping to modify
	 *
	 * @return void
	 */
	public function elasticMapping($mapping)
	{
		$mapping[$this->name] = ['type' => 'geo_point'];
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
		if (isset($criteria['distance'])) {
			$filter[$this->name] = [
				'geo_distance' => [
					'distance' => $criteria['distance'][0],
					$this->name => $criteria['distance'][1],
				],
			];
		}
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
				$this->name => $criteria[0],
				'order' => $criteria[1],
				'unit' => $criteria[2],
				'mode' => 'min',
				'distance_type' => 'arc'
			],
		]);
	}
}
