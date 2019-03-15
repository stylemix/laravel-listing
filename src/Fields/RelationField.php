<?php

namespace Stylemix\Listing\Fields;

use Stylemix\Base\Fields\Base;

/**
 * @property boolean $ajax
 * @method $this ajax(bool $value)
 * @property array   $options
 * @method $this options(array $options)
 * @property array   $source
 * @property array   $queryParam
 * @property string  $otherKey
 * @property boolean $preload
 * @property boolean $preventCasting
 */
class RelationField extends Base
{

	public $component = 'relation-field';

	protected $defaults = [
		'options' => [],
		'ajax' => true,
		'source' => null,
		'otherKey' => 'id',
		'preload' => null,
		'queryParam' => null,
	];

	/** @var \Illuminate\Database\Eloquent\Builder */
	protected $query;

	/** @var callable */
	protected $optionsCallback = null;

	public function toArray()
	{
		if ($this->ajax && $this->query && empty($this->options) && $this->value) {
			$this->options = $this->getOptionsFromQuery($this->value);
		}

		if (!$this->ajax && $this->query && empty($this->options)) {
			$this->options = $this->getOptionsFromQuery();
		}
		else {
			$this->source = [
				'url' => str_plural($this->related),
				'params' => [
					'context' => 'options',
					'primary_key' => $this->otherKey,
				],
			];
		}

		return parent::toArray();
	}

	/**
	 * Set eloquent query builder for options
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 *
	 * @return RelationField
	 */
	public function setQuery(\Illuminate\Database\Eloquent\Builder $query) : RelationField
	{
		$this->query = $query;

		return $this;
	}

	/**
	 * Set callback for resolving options
	 *
	 * @param callable $optionsCallback
	 *
	 * @return $this
	 */
	public function optionsCallback(callable $optionsCallback)
	{
		$this->optionsCallback = $optionsCallback;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	protected function sanitizeRequestInput($value)
	{
		return $this->preventCasting ? $value : intval($value);
	}

	/**
	 * Get options from query. If value is provided, take the options for this value only
	 *
	 * @param mixed $value
	 *
	 * @return array
	 */
	protected function getOptionsFromQuery($value = null)
	{
		$results = $this->query
			->when($value, function ($builder, $value) {
				$builder->where($this->otherKey, $value);
			})
			->get();

		$options = $results->map(function ($model) {
			return (object) $model->toOption($this->otherKey);
		});

		if (is_callable($this->optionsCallback)) {
			$options = call_user_func($this->optionsCallback, $options, $this);
		}

		return $options;
	}

}
