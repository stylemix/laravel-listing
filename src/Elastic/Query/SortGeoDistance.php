<?php

namespace Stylemix\Listing\Elastic\Query;

use Elastica\Exception\InvalidException;

class SortGeoDistance extends Sort
{

	public function __construct($key, $fieldName, $point)
	{
		parent::__construct($key, '_geo_distance', [
			$fieldName => $point,
		]);
	}

	public function setOrder($order)
	{
		if (!in_array($order, ['asc', 'desc'])) {
			throw new InvalidException('Param order should can not be [' . $order . ']');
		}

		$this->setParam('order', $order);

		return $this;
	}

	public function setUnit($unit)
	{
		$this->setParam('unit', $unit);

		return $this;
	}

	public function setMode($mode)
	{
		$this->setParam('mode', $mode);

		return $this;
	}

	public function setDistanceType($type)
	{
		$this->setParam('distance_type', $type);

		return $this;
	}

	public function ignoreUnmapped(bool $value = true)
	{
		$this->setParam('ignore_unmapped', $value);

		return $this;
	}
}
