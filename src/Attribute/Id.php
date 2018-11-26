<?php

namespace Stylemix\Listing\Attribute;

class Id extends Numeric
{

	public function __construct(string $name = null)
	{
		$name = $name ?? 'id';
		parent::__construct($name);
	}

	/**
	 * Adds attribute mappings for elastic search
	 *
	 * @param \Illuminate\Support\Collection $mapping Mapping to modify
	 */
	public function elasticMapping($mapping)
	{
		$mapping[$this->name] = ['type' => 'integer'];
	}

	public function applyFillable($fillable)
	{
		//
	}

	public function formField()
	{
		return null;
	}
}
