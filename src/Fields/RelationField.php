<?php

namespace Stylemix\Listing\Fields;

use Stylemix\Base\Fields\Base;

/**
 * @property boolean $ajax
 * @property array   $options
 * @property boolean $preventCasting
 */
class RelationField extends Base
{

	public $component = 'relation-field';

	protected $defaults = [
		'options' => [],
		'ajax' => true,
		'source' => null,
		'preload' => null,
		'queryParam' => null,
	];

	/** @var \Stylemix\Listing\Attribute\Relation */
	protected $attributeInstance;

	/**
	 * @param \Stylemix\Listing\Attribute\Relation $attributeInstance
	 *
	 * @return RelationField
	 */
	public function attributeInstance($attributeInstance)
	{
		$this->attributeInstance = $attributeInstance;

		return $this;
	}

	public function resolve($resource, $attribute = null)
	{
		if ($this->ajax) {
			$results = $this->attributeInstance->getResults($resource);

			$this->options = $results->map(function ($model) {
				return (object) $model->toOption($this->attributeInstance->getOtherKey());
			});
		}

		parent::resolve($resource, $attribute);
	}

	public function toArray()
	{
		if (!$this->ajax) {
			$results = $this->attributeInstance->getQueryBuilder()->get();

			$this->options = $results->map(function ($model) {
				return (object) $model->toOption($this->attributeInstance->getOtherKey());
			});
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
	 * @inheritdoc
	 */
	protected function sanitizeRequestInput($value)
	{
		return $this->preventCasting ? $value : intval($value);
	}

}
