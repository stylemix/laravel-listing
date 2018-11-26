<?php

namespace Stylemix\Listing\Tests\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Stylemix\Listing\Entity;

class Book extends Entity
{

	/**
	 * Attributes relation
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function dataAttributes(): HasMany
	{
		return $this->hasMany(BookData::class, 'entity_id');
	}

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
