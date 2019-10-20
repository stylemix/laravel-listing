<?php

namespace Stylemix\Listing\Attribute;

use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

/**
 * @property string  $label Label for attribute
 * @property string  $placeholder Placeholder for attribute
 * @property boolean $multiple If attribute has multiple values
 * @property boolean $required Whether an attribute's field should be required
 * @property mixed   $defaultValue Default value for attribute. Can be function that accepts current model attributes.
 * @method $this fillable() Allow attribute to be filled by forms
 * @method $this required() Make this attribute required
 * @method $this multiple() Allow this attribute to have multiple values
 * @method $this search(array $config) Search configuration
 */
abstract class Base extends Fluent
{

	/**
	 * @var string Attribute type
	 */
	public $type;

	/**
	 * @var string Attribute name
	 */
	public $name;

	/**
	 * @var string Attribute name for filling
	 */
	public $fillableName;

	/**
	 * @var string Attribute name for filtering
	 */
	public $filterableName;

	/**
	 * @var string Attribute name for sorting
	 */
	public $sortableName;

	/**
	 * Base constructor.
	 *
	 * @param string $name Attribute name
	 */
	public function __construct($name)
	{
		$this->type = $this->type ?: snake_case(class_basename($this));
		$this->name = $name;
		$this->fillableName = $name;
		$this->filterableName = $name;
		$this->sortableName = $name;
		$this->label = $this->getLabel();

		parent::__construct([]);
	}

	/**
	 * Adds fillable properties for attribute
	 *
	 * @param \Illuminate\Support\Collection $fillable
	 */
	public function applyFillable($fillable)
	{
		$fillable->push($this->fillableName);
	}

	/**
	 * Attribute key that attribute is responsible for fill
	 *
	 * @return string
	 */
	public function fills()
	{
		return $this->fillableName;
	}

	/**
	 * Checks whether the value for attribute should be marked as empty and removed.
	 * Passed value is always taken from fillable key.
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public function isValueEmpty($value)
	{
		return empty($value);
	}

	/**
	 * @inheritDoc
	 */
	public function filterKeys() : array
	{
		return [$this->name];
	}

	/**
	 * @inheritDoc
	 */
	public function sortKeys() : array
	{
		return [$this->sortableName];
	}

	/**
	 * Adds attribute casts
	 *
	 * @param \Illuminate\Support\Collection $casts
	 */
	public function applyCasts($casts)
	{

	}

	/**
	 * Adds attribute mappings for elastic search
	 *
	 * @param \Illuminate\Support\Collection $mapping Mapping to modify
	 */
	abstract public function elasticMapping($mapping);

	/**
	 * Manipulate data when indexing to ES
	 *
	 * @param \Illuminate\Support\Collection $data
	 * @param \Stylemix\Listing\Entity $model
	 */
	public function applyIndexData($data, $model)
	{

	}

	/**
	 * Manipulate data when hydrating from ES
	 *
	 * @param \Illuminate\Support\Collection $data
	 * @param \Stylemix\Listing\Entity $model
	 */
	public function applyHydratingIndexData($data, $model)
	{

	}

	/**
	 * Manipulate data when calling toArray
	 *
	 * @param \Illuminate\Support\Collection $data
	 * @param \Stylemix\Listing\Entity $model
	 */
	public function applyArrayData($data, $model)
	{

	}

	/**
	 * Actions before model is saved
	 *
	 * @param \Illuminate\Support\Collection $data
	 * @param \Stylemix\Listing\Entity $model
	 */
	public function saving($data, $model)
	{

	}

	/**
	 * Actions after model is saved
	 *
	 * @param \Stylemix\Listing\Entity $model
	 */
	public function saved($model)
	{

	}

	/**
	 * Actions before model is deleted
	 *
	 * @param \Stylemix\Listing\Entity $model
	 */
	public function deleting($model)
	{

	}

	/**
	 * Returns default value for the attribute
	 *
	 * @param \Illuminate\Support\Collection $attributes
	 *
	 * @return mixed
	 */
	public function applyDefaultValue($attributes)
	{
		$defaultValue = $this->defaultValue;

		return $defaultValue instanceof \Closure ? $defaultValue($attributes) : $defaultValue;
	}

	/**
	 * @inheritdoc
	 */
	public function toArray()
	{
		return array_merge(['name' => $this->name, 'type' => $this->type], parent::toArray());
	}

	/**
	 * Creates new instance of the attribute
	 *
	 * @param mixed ...$arguments
	 *
	 * @return static
	 */
	public static function make(...$arguments)
	{
		return new static(...$arguments);
	}

	/**
	 * Get label from translations by attribute name. Defaults to studly case from name
	 *
	 * @return string
	 */
	protected function getLabel()
	{
		return Arr::get(trans('attributes'), $this->name, function () {
			return Str::title(str_replace('_', ' ', Str::snake($this->name)));
		});
	}

	/**
	 * If is value is callable, calls it with additional arguments or returns as is
	 *
	 * @param mixed $value
	 * @param mixed ...$arguments
	 *
	 * @return mixed
	 */
	protected function evaluate($value, ...$arguments)
	{
		return $value instanceof \Closure ? $value($this, ...$arguments) : $value;
	}

}
