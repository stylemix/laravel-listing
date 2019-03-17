<?php

namespace Stylemix\Listing\Attribute;

/**
 * @property string $saleName
 * @property string $saleLabel
 */
class Price extends Currency
{

	protected $finalName;

	public function __construct(string $name, $saleName = null)
	{
		parent::__construct($name);
		$this->saleName        = $saleName ?? $this->name . '_sale';
		$this->finalName       = $this->name . '_final';
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
		return [$this->fillableName, $this->saleName];
	}

	public function applyCasts($casts)
	{
		parent::applyCasts($casts);
		$casts->put($this->saleName, 'float');
	}

	public function elasticMapping($mapping)
	{
		parent::elasticMapping($mapping);

		$mapping[$this->saleName] = [
			'type' => 'scaled_float',
			'scaling_factor' => 2
		];

		$mapping[$this->finalName] = [
			'type' => 'scaled_float',
			'scaling_factor' => 2
		];
	}

	public function applyIndexData($data, $model)
	{
		parent::applyArrayData($data, $model);

		$data[$this->finalName] = $data[$this->name] ?? 0;

		if (isset($data[$this->saleName]) && $data[$this->saleName] != 0) {
			$data[$this->finalName] = $data[$this->saleName];
		}
	}

	protected function getSaleLabel()
	{
		return array_get(trans('attributes'), $this->saleName, function () {
			return 'Sale ' . $this->label;
		});
	}

}
