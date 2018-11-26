<?php

namespace Stylemix\Listing\Attribute;

use Stylemix\Base\Fields\Number;

/**
 * @property string $saleLabel
 */
class PriceWithSale extends Currency
{
	protected $saleName;

	protected $finalName;

	public function __construct(string $name, $saleName = null) {
		parent::__construct($name);
		$this->saleName = $saleName ?? $this->name . '_sale';
		$this->finalName = $this->name . '_final';
		$this->aggregatedField = $this->finalName;
		$this->saleLabel = $this->getSaleLabel();
	}

	public function applyFillable($fillable)
	{
		parent::applyFillable($fillable);
		$fillable->push($this->saleName);
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
			"scaling_factor" => 100
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

	/**
	 * @inheritdoc
	 */
	public function formField()
	{
		return [
			Number::make($this->fillableName)
				->min(0)
				->multiple($this->multiple)
				->label($this->label),
			Number::make($this->saleName)
				->rules('nullable')
				->min(0)
				->multiple($this->multiple)
				->label($this->saleLabel)
		];
	}

	protected function getSaleLabel()
	{
		return array_get(trans('attributes'), $this->saleName, function () {
			return 'Sale ' . $this->label;
		});
	}

}
