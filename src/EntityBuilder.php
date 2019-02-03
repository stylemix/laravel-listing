<?php

namespace Stylemix\Listing;

use Illuminate\Database\Eloquent\Builder;

class EntityBuilder extends Builder
{

	/**
	 * @var \Stylemix\Listing\Entity
	 */
	protected $model;

	public function insert(array $values)
	{
		return parent::insert($values);
	}

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

		if ($updated = parent::update($values)) {
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
		$attributes = array_except($values, $this->model->dbFields);
		$values = array_only($values, $this->model->dbFields);

		return [$values, $attributes];
	}

}
