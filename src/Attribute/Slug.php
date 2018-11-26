<?php

namespace Stylemix\Listing\Attribute;

/**
 * @property string $generateFrom
 */
class Slug extends Keyword
{

	public function __construct(string $name = null) {
		$name = $name ?? 'slug';
		$this->generateFrom = 'title';
		parent::__construct($name);
	}

	public function applyDefaultValue($attributes)
	{
		if ($this->generateFrom instanceof \Closure) {
			return call_user_func($this->generateFrom, $attributes);
		}
		else {
			return str_slug($attributes->get($this->generateFrom) ?? str_random(8));
		}
	}

}
