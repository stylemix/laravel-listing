<?php

namespace Stylemix\Listing\Tests\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Stylemix\Listing\Attribute\Enum;
use Stylemix\Listing\Attribute\Id;
use Stylemix\Listing\Attribute\Text;
use Stylemix\Listing\Entity;

class DummyBook extends Entity
{

	protected $table = 'books';

	/**
	 * Attribute definitions
	 *
	 * @return array
	 */
	protected static function attributeDefinitions(): array
	{
		return [
			Id::make(),
			Text::make('title'),
			Enum::make('enum', DummyEnum::class),
		];
	}
}
