<?php

namespace Stylemix\Listing\Fields;

use Stylemix\Base\Fields\Base;

class LocationField extends Base
{

	public $component = 'location-field';

	public function __construct($attribute)
	{
		parent::__construct($attribute);

		$this->value = [
			'latlng' => null,
			'address' => null,
			'city' => null,
			'zip' => null,
			'region' => null,
			'country' => null,
		];
	}

}
