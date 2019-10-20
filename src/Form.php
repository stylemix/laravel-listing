<?php

namespace Stylemix\Listing;

use Illuminate\Support\Arr;
use Stylemix\Listing\Attribute\Base;

class Form
{

	/**
	 * Map of attribute types to form generator functions
	 *
	 * @var array
	 */
	protected $types = [];

	protected $byName = [];

	protected $callback = null;

	protected $extends = [];

	/**
	 * Register a fields builder function for attribute class
	 *
	 * @param string   $class   Attribute class
	 * @param callable $builder Function with signature function($attribute)
	 */
	public function register($class, callable $builder)
	{
		$this->types[$class] = $builder;
	}

	/**
	 * Register a fields builder function by attribute name.
	 *
	 * @param string   $name   Attribute name
	 * @param callable $builder Function with signature function($attribute)
	 */
	public function registerByName($name, callable $builder)
	{
		$this->byName[$name] = $builder;
	}

	/**
	 * Add function that performs some modifications to specific field by its attribute
	 *
	 * @param string   $attribute
	 * @param callable $callback
	 *
	 * @return $this
	 */
	public function extend($attribute, callable $callback)
	{
		$this->extends[] = compact('attribute', 'callback');

		return $this;
	}

	/**
	 * Add function that performs some modifications to all fields
	 *
	 * @param callable $callback
	 *
	 * @return $this
	 */
	public function extendAll(callable $callback)
	{
		$this->callback = $callback;

		return $this;
	}

	protected function prepare($fields, Base $attribute)
	{
		$fields = collect($fields)->keyBy('attribute');

		if (is_callable($this->callback)) {
			foreach ($fields as $field) {
				call_user_func($this->callback, $field, $attribute);
			}
		}

		foreach ($this->extends as $extend) {
			// No need to extend if field doesn't exists
			if ($attribute->name != $extend['attribute']) {
				continue;
			}

			foreach ($fields as $key => $field) {
				$fields[$key] = call_user_func($extend['callback'], $field, $attribute);
			}
		}

		return $fields->all();
	}

	/**
	 * Generate form fields for given attributes
	 *
	 * @param \Stylemix\Listing\AttributeCollection $attributes
	 *
	 * @return \Stylemix\Base\Fields\Base[]|\Illuminate\Support\Collection
	 */
	public function forAttributes(AttributeCollection $attributes)
	{
		$form = collect();

		$attributes->each(function (Base $attribute) use (&$form) {
			if (!($builder = $this->getBuilderForAttribute($attribute))) {
				return;
			}

			$fields = Arr::wrap($builder($attribute));
			$fields = $this->prepare($fields, $attribute);
			$form   = $form->concat($fields);
		});

		return $form->values();
	}

	/**
	 * @param Base $attribute
	 *
	 * @return callable|null
	 */
	protected function getBuilderForAttribute($attribute)
	{
		if (isset($this->byName[$attribute->name])) {
			return $this->byName[$attribute->name];
		}

		// Assume that registrations of attribute types
		// ordered from ancestor classes to descendant classes
		// So search from end of the mappings
		foreach (array_reverse($this->types) as $class => $builder) {
			if ($attribute instanceof $class) {
				return $builder;
			}
		}

		return null;
	}
}
