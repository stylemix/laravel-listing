<?php

namespace Stylemix\Listing\Attribute;

use Illuminate\Support\Arr;

/**
 * @property mixed $source Enum keywords source
 */
class Enum extends Keyword
{

	/**
	 * Enum constructor.
	 *
	 * @param string $name
	 * @param string $enumClass
	 */
	public function __construct($name, $enumClass = null)
	{
		parent::__construct($name);

		if ($enumClass) {
			$this->source = $enumClass::choices();
		}
	}

	public function applyIndexData($data, $model)
	{
		$value = $data->get($this->name);
		$data[$this->name . '_text'] = $value ? Arr::get($this->source, $value) : null;
	}

	public function getSelectOptions()
	{
		$options = collect($this->source)->map(function ($label, $value) {
			return compact('label', 'value');
		});

		return $options->values()->all();
	}

}
