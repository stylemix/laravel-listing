<?php

namespace Stylemix\Listing\Tests\Unit;

use Stylemix\Listing\Elastic\Builder;
use Stylemix\Listing\Tests\Models\DummyBook;
use Stylemix\Listing\Tests\TestCase;

class QueryBuilderTest extends TestCase
{

	public function testSearch()
	{
		$search = DummyBook::search();
		$this->assertInstanceOf(Builder::class, $search);

		$raw = $search->build();
		$this->assertArraySubset([
			'size' => $search->getPerPage(),
		], $raw);

		$raw = $search
			->page(2)
			->perPage(30)
			->build();
		$this->assertArraySubset([
			'size' => $search->getPerPage(),
			'from' => 30,
		], $raw);
	}

	public function testWhere()
	{
		$result = DummyBook::search()
			->where('id', 1)
			->build();

		$this->assertArrayHasKey('query', $result);
		$this->assertEquals([
			'term' => ['id' => 1],
		], $result['query']);

		// Bool AND query
		$result = DummyBook::search()
			->where('id', 1)
			->where('numeric', 2)
			->build();

		$this->assertArraySubset([
			'bool' => [
				'must' => [
					[ 'match_all' => new \stdClass() ]
				],
				'filter' => [],
			],
		], $result['query']);

		// Bool with NOT query
		$result = DummyBook::search()
			->where('id', 1)
			->whereNot('numeric', 2)
			->build();

		$this->assertArraySubset([
			'bool' => [
				'must' => [
					[ 'match_all' => new \stdClass() ]
				],
				'filter' => [],
				'must_not' => [],
			],
		], $result['query']);
	}

	public function testWhereNull()
	{
		$result = DummyBook::search()
			->where('term', null)
			->build();

		$this->assertEquals([
			'bool' => [
				'must' => [
					[ 'match_all' => new \stdClass() ]
				],
				'must_not' => [
					[
						'exists' => [
							'field' => 'term',
						],
					],
				],
			],
		], $result['query']);

		$result = DummyBook::search()
			->whereNot('term', null)
			->build();
		$this->assertEquals([
			'exists' => [
				'field' => 'term',
			],
		], $result['query']);

		$result = DummyBook::search()
			->whereNot('term', null)
			->where('numeric', null)
			->build();
		$this->assertEquals([
			'bool' => [
				'must' => [
					[ 'match_all' => new \stdClass() ]
				],
				'filter' => [
					['exists' => ['field' => 'term']],
				],
				'must_not' => [
					['exists' => ['field' => 'numeric']],
				],
			],
		], $result['query']);
	}

	public function testQueryString()
	{
		$query = DummyBook::search()
			->queryString('keyword')
			->getQuery();

		/** @var \Elastica\Query\QueryString $queryString */
		$queryString = $query->getQuery();
		$this->assertInstanceOf(\Elastica\Query\QueryString::class, $queryString);
		$this->assertEquals('*keyword*', $queryString->getParam('query'));
	}

	public function testFilter()
	{
		$result = DummyBook::search()
			->filter('id', 1)
			->filter('numeric', 2)
			->filter('price', 3)
			->filter('boolean', true)
			->filter('keyword', 'foo')
			->build();

		$this->assertArraySubset([
			'bool' => [
				'filter' => [],
			],
		], $result['post_filter']);
	}

	public function testRandom()
	{
		$result = DummyBook::search()
			->random(10)
			->build();
		$this->assertEquals([
			'function_score' => [
				'functions' => [['random_score' => ['seed' => 10]]],
			]
		], $result['query']);

		$result = DummyBook::search()
			->where('id', 1)
			->random(20)
			->build();
		$this->assertArraySubset([
			'function_score' => [
				'functions' => [
					[
						'random_score' => [],
						'filter' => [],
					],
				],
			]
		], $result['query']);
	}

	public function testEarlyRandomThrowsException()
	{
		$this->expectException(\BadMethodCallException::class);
		DummyBook::search()
			->random(10)
			->where('id', 1)
			->build();
	}

	public function testSort()
	{
		$result = DummyBook::search()
			->sort('id')
			->build();
		$this->assertEquals([
			['id' => 'asc'],
		], $result['sort']);

		$result = DummyBook::search()
			->sort('id')
			->sort('numeric', 'desc')
			->build();
		$this->assertEquals([
			['id' => 'asc'],
			['numeric' => 'desc'],
		], $result['sort']);

		$builder = DummyBook::search();
		$result = $builder
			->sort([
				'enum' => 'asc',
				'numeric' => 'asc',
				'location' => '12,34',
			])
			->build();
		$this->assertArraySubset([
			['enum' => 'asc'],
			['numeric' => 'asc'],
			['_geo_distance' => []],
		], $result['sort']);

		$sortMap = $this->invokeProtected('getSortMap', $builder);
		$this->assertEquals([
			'enum' => 0,
			'numeric' => 1,
			'location' => 2,
		], $sortMap);

		$builder->sort('_score', null, true);
		$result = $builder->build();
		$this->assertArraySubset([
			'_score',
			['enum' => 'asc'],
			['numeric' => 'asc'],
			['_geo_distance' => []],
		], $result['sort']);

		$sortMap = $this->invokeProtected('getSortMap', $builder);
		$this->assertEquals([
			'enum' => 1,
			'numeric' => 2,
			'location' => 3,
		], $sortMap);

		$this->expectException(\BadMethodCallException::class);
		DummyBook::search()->sort('foo');
	}

	public function testAggregation()
	{
		$result = DummyBook::search()
			->aggregate('enum')
			->build();
		$this->assertArraySubset([
			'enum' => [
				'aggs' => [
					'available' => [],
				],
			],
		], $result['aggs']);

		$result = DummyBook::search()
			->aggregate('numeric')
			->aggregate('price')
			->aggregate('boolean')
			->aggregate('keyword')
			->aggregate('enum')
			->aggregate('author')
			->build();
		$this->assertArraySubset([
			'numeric' => [],
			'boolean' => [],
			'enum' => [],
			'author' => [],
		], $result['aggs']);

		$this->expectException(\BadMethodCallException::class);
		DummyBook::search()->aggregate('foo');
	}

	/**
	 * @param string $method
	 * @param \Stylemix\Listing\Elastic\Builder $object
	 *
	 * @return mixed
	 * @throws \ReflectionException
	 */
	protected function invokeProtected(string $method, $object)
	{
		$reflectionMethod = new \ReflectionMethod(get_class($object), $method);
		$reflectionMethod->setAccessible(true);

		return ($reflectionMethod)->invoke($object);
	}
}
