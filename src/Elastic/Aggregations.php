<?php

namespace Stylemix\Listing\Elastic;

use Illuminate\Support\Collection;
use Stylemix\Listing\Entity;

class Aggregations extends Collection
{
	public static function build(Entity $entity, array $raw)
	{
		$collection = self::make($raw);

		foreach ($entity::getAttributeDefinitions() as $attribute) {
			$attribute->collectAggregations($collection, $raw);
		}

		return $collection;
	}
}
