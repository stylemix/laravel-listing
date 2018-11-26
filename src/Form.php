<?php

namespace Stylemix\Listing;

use Illuminate\Support\Collection;
use Stylemix\Listing\Attribute\Base;

class Form
{
	protected $callback = null;

	protected $modifications = [];

	protected $replacements = [];

	/**
	 * Add function that performs some modifications to specific field by its attribute
	 *
	 * @param string   $attribute
	 * @param callable $callback
	 *
	 * @return $this
	 */
	public function extend($attribute, $callback)
	{
		$this->modifications[] = compact('attribute', 'callback');

		return $this;
	}

	/**
	 * Add function that performs some modifications to all fields
	 *
	 * @param callable $callback
	 *
	 * @return $this
	 */
	public function extendAll($callback)
	{
		$this->callback = $callback;

		return $this;
	}

	/**
	 * Add function that replaces specific field by its attribute
	 *
	 * @param string   $attribute
	 * @param callable $callback
	 *
	 * @return $this
	 */
	public function replace($attribute, $callback)
	{
		$this->replacements[] = compact('attribute', 'callback');

		return $this;
	}

	protected function prepare($fields, $attribute)
	{
		$fields = collect($fields)->keyBy('attribute');

		if (is_callable($this->callback)) {
			foreach ($fields as $field) {
				call_user_func($this->callback, $field, $attribute);
			}
		}

		foreach ($this->modifications as $modification) {
			// No need to modify if field doesn't exists
			if (!($field = $fields->get($modification['attribute']))) {
				continue;
			}

			call_user_func($modification['callback'], $field, $attribute);
		}

		foreach ($this->replacements as $replace) {
			// No need to replace if field doesn't exists
			if (!($field = $fields->get($replace['attribute']))) {
				continue;
			}

			$fields[$replace['attribute']] = call_user_func($replace['callback'], $field, $attribute);
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
			$fields = array_wrap($attribute->formField());
			$fields = $this->prepare($fields, $attribute);
			$form = $form->concat($fields);
		});

		return $form->values();
	}
}
