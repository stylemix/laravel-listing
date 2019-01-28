<?php

namespace Stylemix\Listing\Attribute;

use Stylemix\Listing\Contracts\Searchable;

/**
 * @property $editor Use editor in form
 * @method $this editor() Use editor in form
 */
class LongText extends Base implements Searchable
{
	/**
	 * Adds attribute mappings for elastic search
	 *
	 * @param \Illuminate\Support\Collection $mapping Mapping to modify
	 */
	public function elasticMapping($mapping)
	{
		$mapping[$this->name] = ['type' => 'text'];
	}

	/**
	 * Adds attribute casts
	 *
	 * @param \Illuminate\Support\Collection $casts
	 */
	public function applyCasts($casts)
	{
		$casts->put($this->name, 'string');
	}

}
