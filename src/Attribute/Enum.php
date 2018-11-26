<?php

namespace Stylemix\Listing\Attribute;

use Stylemix\Base\Fields\Select;

/**
 * @property mixed $source Enum keywords source
 */
class Enum extends Keyword
{

	public function applyIndexData($data, $model)
	{
		$value = $data->get($this->name);
		$data[$this->name . '_text'] = $value ? array_get($this->source, $value) : null;
	}

	/**
	 * @inheritdoc
	 */
	public function formField()
	{
		return Select::make($this->fillableName)
			->required($this->required)
			->multiple($this->multiple)
			->options($this->getSelectOptions())
			->label($this->label);
	}

	protected function getSelectOptions()
	{
		$options = collect($this->source)->map(function ($label, $value) {
			return compact('label', 'value');
		});

		return $options->values()->all();
	}

}
