<?php

namespace Stylemix\Listing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

/**
 * @property \Stylemix\Listing\Entity $model
 */
class EntityBuilder extends Builder
{

	/**
	 * Stores resolved (sql) database fields for each entity model
	 *
	 * @var array
	 */
	protected static $resolvedDbFields = [];

	public function insertGetId(array $values, $sequence = null)
	{
		list ($values, $attributes) = $this->splitAttributeValues($values);

		if ($id = parent::insertGetId($values, $sequence)) {
			foreach ($attributes as $key => $value) {
				if (is_null($value)) {
					continue;
				}

				$this->model->dataAttributes->push(
					$this->model->dataAttributes()->forceCreate([
						'entity_id' => $id,
						'name' => $key,
						'value' => $value,
					])
				);
			}
		}

		return $id;
	}

	public function update(array $values)
	{
		list ($values, $attributes) = $this->splitAttributeValues($values);

		$updated = parent::update($values);

		foreach ($attributes as $name => $value) {
			/** @var \Stylemix\Listing\EntityData $attribute */
			$attribute = $this->model->dataAttributes->where('name', $name)->first();

			if (!$attribute) {
				if (is_null($value)) {
					continue;
				}
				$this->model->dataAttributes->push(
					$attribute = $this->model->dataAttributes()->newModelInstance([
						'name' => $name,
					])
				);
			}

			$attribute->value = $value;
			$attribute->entity_id = $this->model->getKey();

			if (is_null($attribute->value)) {
				$attribute->delete();
			} else {
				$attribute->save();
			}
		}

		return $updated;
	}

	public function get($columns = ['*'])
	{
		$collection = parent::get($columns);

		return $collection->each(function ($entity) {
			$this->hydrateDataAttributes($entity);
		});
	}

	protected function hydrateDataAttributes(Entity $entity)
	{
		$values = array_merge($entity->getAttributes(), $entity->dataAttributes->pluck('value', 'name')->all());
		$entity->setRawAttributes($values, true);
	}

	protected function splitAttributeValues(array $values)
	{
		$attributes = Arr::except($values, $this->getDbFields($this->model));
		$values = Arr::only($values, $this->getDbFields($this->model));

		return [$values, $attributes];
	}

	/**
	 * Get database fields for model
	 *
	 * @param \Illuminate\Database\Eloquent\Model $model
	 *
	 * @return array
	 */
	protected function getDbFields($model)
	{
		$class = get_class($model);

		if (isset(static::$resolvedDbFields[$class])) {
			return static::$resolvedDbFields[$class];
		}

		static::$resolvedDbFields[$class] = $fields = Schema::getColumnListing($model->getTable());

		return $fields;
	}

}
