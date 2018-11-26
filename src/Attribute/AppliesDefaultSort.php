<?php

namespace Stylemix\Listing\Attribute;

trait AppliesDefaultSort
{

	/**
	 * @inheritdoc
	 */
	public function applySort($criteria, $sort, $key) : void
	{
		$sort->put($key, [
			$key => $criteria,
		]);
	}

}
