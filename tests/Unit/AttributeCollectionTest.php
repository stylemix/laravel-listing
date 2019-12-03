<?php

namespace Stylemix\Listing\Tests\Unit;

use Stylemix\Listing\Attribute\Price;
use Stylemix\Listing\Attribute\Relation;
use Stylemix\Listing\Attribute\Text;
use Stylemix\Listing\AttributeCollection;
use Stylemix\Listing\Tests\Models\DummyBook;
use Stylemix\Listing\Tests\TestCase;

class AttributeCollectionTest extends TestCase
{

	public function testAllKeys()
	{
		$collection = AttributeCollection::make([
			Text::make('text'),
			Price::make('price')->withSale(),
			Relation::make('relation')
		])->keyBy('name');

		$this->assertEquals([
			'text',
			'price',
			'price_sale',
			'relation',
			'relation_id',
		], $collection->allKeys());
	}

	public function testSortables()
	{
		$attributes = DummyBook::getAttributeDefinitions()->implementsSortable();
		$this->assertEquals([
			'id',
			'numeric',
			'price',
			'price_sale',
			'boolean',
			'keyword',
			'enum',
			'date',
			'title',
			'location',
		], $attributes->keys()->all());
	}
}
