<?php

namespace Stylemix\Listing\Fields;

use Stylemix\Base\Fields\Base;

class LocationField extends Base
{

	public $component = 'location-field';

	protected $defaults = [
		'types' => null,
		'withMap' => null,
	];

	public function __construct($attribute)
	{
		parent::__construct($attribute);

		$this->value = [
			'latlng' => null,
			'zoom' => null,
			'address' => null,
			'city' => null,
			'zip' => null,
			'region' => null,
			'country' => null,
		];
	}

	protected function sanitizeRequestInput($value)
	{
		if (isset($value['zoom'])) {
			$value['zoom'] = intval($value['zoom']);
		}

		return $value;
	}
}
