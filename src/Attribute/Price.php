<?php

namespace Stylemix\Listing\Attribute;

use Elastica\Query\AbstractQuery;
use Illuminate\Support\Arr;

/**
 * @method $this withSale($withSale = true) Make attribute to use sale value
 * @property bool $withSale
 * @property string $saleName
 * @property string $saleLabel
 */
class Price extends Numeric
{

	protected $finalName;

	public function __construct(string $name, $saleName = null)
	{
		parent::__construct($name);
		$this->saleName        = $saleName ?? $this->name . '_sale';
		$this->finalName       = $this->name . '_final';
		$this->filterableName  = $this->finalName;
		$this->sortableName    = $this->finalName;
		$this->aggregatedField = $this->finalName;
		$this->saleLabel       = $this->getSaleLabel();
	}

	public function applyFillable($fillable)
	{
		parent::applyFillable($fillable);
		$fillable->push($this->saleName);
	}

	/**
	 * @inheritdoc
	 */
	public function fills()
	{
		return $this->withSale ? [$this->fillableName, $this->saleName] : [$this->fillableName];
	}

	public function applyCasts($casts)
	{
		$casts->put($this->name, 'float');
		$casts->put($this->saleName, 'float');
	}

	public function elasticMapping($mapping)
	{
		$mapping[$this->name] = [
			'type' => 'scaled_float',
			'scaling_factor' => 2,
		];

		if ($this->withSale) {
			$mapping[$this->saleName] = [
				'type' => 'scaled_float',
				'scaling_factor' => 2,
			];
			$mapping[$this->finalName] = [
				'type' => 'scaled_float',
				'scaling_factor' => 2,
			];
		}
	}

	/**
	 * @inheritDoc
	 */
	public function filterKeys() : array
	{
		return $this->withSale ? [$this->name, $this->saleName] : [$this->name];
	}

	/**
	 * @inheritDoc
	 */
	public function sortKeys() : array
	{
		return $this->withSale ? [$this->name, $this->saleName] : [$this->name];
	}

	/**
	 * @inheritDoc
	 */
	public function applyFilter($criteria, $key) : AbstractQuery
	{
		if (is_array($criteria)) {
			$criteria = array_map('floatval', $criteria);
		}

		$fieldName = $key;

		if ($this->withSale && $key == $this->name) {
			// If requested filter by attribute's name,
			// final name should be used in query
			$fieldName = $this->finalName;
		}

		return $this->createRangeQuery($criteria, $fieldName);
	}

	/**
	 * @inheritDoc
	 */
	public function applyIndexData($data, $model)
	{
		parent::applyArrayData($data, $model);

		if ($this->withSale) {
			$data[$this->finalName] = $data[$this->name] ?? 0;
			if (isset($data[$this->saleName]) && $data[$this->saleName] != 0) {
				$data[$this->finalName] = $data[$this->saleName];
			}
		}
	}

	protected function getSaleLabel()
	{
		return Arr::get(trans('attributes'), $this->saleName, function () {
			return 'Sale ' . $this->label;
		});
	}

}
