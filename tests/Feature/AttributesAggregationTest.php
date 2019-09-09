<?php

namespace Stylemix\Listing\Tests\Feature;

use Stylemix\Listing\Testing\ElasticSearchIndexing;
use Stylemix\Listing\Tests\Models\DummyBook;
use Stylemix\Listing\Tests\TestCase;

class AttributesAggregationTest extends TestCase
{

	use ElasticSearchIndexing;

	protected function setUp()
	{
		parent::setUp();
		$this->enableElasticSearchIndexing();
	}

	public function testAggregationQuery()
	{
		factory(DummyBook::class, 3)->create();

		$result = DummyBook::search()
			->aggregations([
				'enum' => true,
			])
			->get();

		$this->assertArrayHasKey('enum', $result->getAggregations());
	}
}
