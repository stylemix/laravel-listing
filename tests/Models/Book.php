<?php

namespace Stylemix\Listing\Tests\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Stylemix\Listing\Entity;

class Book extends Entity
{

	/**
	 * Attribute definitions
	 *
	 * @return array
	 */
	protected static function attributeDefinitions(): array
	{
		return [];
	}
}
