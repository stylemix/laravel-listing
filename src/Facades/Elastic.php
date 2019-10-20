<?php

namespace Stylemix\Listing\Facades;

use Elastica\QueryBuilder;
use Illuminate\Support\Facades\Facade;

/**
 * Facade class Elastic
 *
 * @method static \Elastica\QueryBuilder\DSL\Query query() Query DSL.
 * @method static \Elastica\QueryBuilder\DSL\Aggregation aggregation() Aggregation DSL.
 * @method static \Elastica\QueryBuilder\DSL\Suggest suggest() Suggest DSL.
 */
class Elastic extends Facade
{

	protected static function getFacadeAccessor()
	{
		return QueryBuilder::class;
	}
}
