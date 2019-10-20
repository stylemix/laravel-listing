<?php

namespace Stylemix\Listing\Tests\Unit\Elastic;

use Stylemix\Listing\Elastic\Query\Sort;
use Stylemix\Listing\Tests\TestCase;

class SortTest extends TestCase
{

	public function testToArray()
	{
		$sort = (new Sort('key', 'field', 'asc'));
		$this->assertEquals('key', $sort->getKey());
		$this->assertEquals([
			'field' => 'asc',
		], $sort->toArray());

		$sort = (new Sort('key', 'field', ['order' => 'asc']));
		$this->assertEquals([
			'field' => 'asc',
		], $sort->toArray());

		$sort = (new Sort('key', 'field', ['order' => 'asc', 'mode' => 'avg']));
		$this->assertEquals([
			'field' => ['order' => 'asc', 'mode' => 'avg'],
		], $sort->toArray());
	}
}
