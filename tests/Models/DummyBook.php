<?php

namespace Stylemix\Listing\Tests\Models;

use Stylemix\Listing\Attribute\Boolean;
use Stylemix\Listing\Attribute\Date;
use Stylemix\Listing\Attribute\Enum;
use Stylemix\Listing\Attribute\Id;
use Stylemix\Listing\Attribute\Keyword;
use Stylemix\Listing\Attribute\Location;
use Stylemix\Listing\Attribute\Numeric;
use Stylemix\Listing\Attribute\Price;
use Stylemix\Listing\Attribute\Relation;
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
			Numeric::make('numeric'),
			Price::make('price')->withSale(),
			Boolean::make('boolean'),
			Keyword::make('keyword'),
			Enum::make('enum', DummyEnum::class),
			Date::make('date'),
			Text::make('title'),
			Location::make('location'),
			Relation::make('author', 'user'),
		];
	}
}
