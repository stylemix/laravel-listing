<?php

namespace Stylemix\Listing\Contracts;

use Stylemix\Listing\Entity;

interface Mutatable
{

	/**
	 * Return mutated value when requesting attribute
	 *
	 * @param \Stylemix\Listing\Entity $model
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getMutator(Entity $model, $key);

	/**
	 * Sets mutated value when updating attribute value
	 *
	 * @param \Stylemix\Listing\Entity $model
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function setMutator(Entity $model, $key, $value);
}
