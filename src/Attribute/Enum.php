<?php

namespace Stylemix\Listing\Attribute;

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

	public function getSelectOptions()
	{
		$options = collect($this->source)->map(function ($label, $value) {
			return compact('label', 'value');
		});

		return $options->values()->all();
	}

}
