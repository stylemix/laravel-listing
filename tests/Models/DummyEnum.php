<?php

namespace Stylemix\Listing\Tests\Models;

use Konekt\Enum\Enum;

class DummyEnum extends Enum
{

	const VAL1 = 'val1';
	const VAL2 = 'val2';

	public static $labels = [
		self::VAL1 => 'Value 1',
		self::VAL2 => 'Value 2',
	];
}
