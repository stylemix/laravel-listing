<?php

namespace Stylemix\Listing\Attribute;

use Stylemix\Listing\Elastic\Query\Sort;

trait AppliesDefaultSort
{

	/**
	 * @inheritdoc
	 */
	public function applySort($criteria, $key) : Sort
	{
		return new Sort($key, $this->sortableName ?: $this->name, $criteria);
	}

}
