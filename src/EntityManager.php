<?php

namespace Stylemix\Listing;

use Illuminate\Container\Container;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stylemix\Listing\Attribute\Relation;

class EntityManager extends Container
{
	/**
	 * The current globally available container (if any).
	 *
	 * @var static
	 */
	protected static $instance;

	/**
	 * Create schema of data table for entity table
	 *
	 * @param string $base
	 */
	public function createDataTable($base)
	{
		Schema::create($base . '_data', function (Blueprint $table) use ($base) {
			$table->increments('id');
			$table->unsignedInteger('entity_id');
			$table->foreign('entity_id')
				->references('id')->on($base)
				->onDelete('cascade')
				->onUpdate('cascade');
			$table->string('lang', 5)->nullable();
			$table->string('name', 32);
			$table->longText('value');
		});
	}

	/**
	 * Drop data table for entity table
	 *
	 * @param string $base
	 */
	public function dropDataTable($base)
	{
		Schema::dropIfExists($base . '_data');
	}

    /**
     * Register an entity class
     *
     * @param string $class
     * @param string $name
     */
	public function entity($class, $name = null)
	{
		$name = $name ?? snake_case(class_basename($class));
		$this->bind($name, $class);
	}

	/**
	 * Get attributes for the entity
	 *
	 * @param object|string $entityClass
	 *
	 * @return \Stylemix\Listing\AttributeCollection
	 */
	public static function attributes($entityClass)
	{
		return $entityClass::getAttributeDefinitions();
	}

	/**
	 * Mark all entity records as un-indexed
	 *
	 * @param string $entity
	 */
	public function resetIndexed($entity)
	{
		DB::table($this->make($entity)->getTable())->update(['indexed_at' => null]);
	}

	/**
	 * @param \Stylemix\Listing\Entity $model
	 * @param callable                 $attributeCallback Additional callback for filtering out attributes
	 *
	 * @return array
	 */
	public function getEntityRelatedAttributes(Entity $model, $attributeCallback = null)
	{
		$attributes = [];

		foreach (array_keys($this->getBindings()) as $entity) {
			/** @var Relation $attribute */
			$entityInstance = $this->make($entity);
			$entityClass = get_class($entityInstance);
			$attributes[$entityClass] = [];

			foreach ($entityInstance::getAttributeDefinitions()->whereInstanceof(Relation::class) as $attribute) {
				// Don't take attributes that doesn't represent related entity
				if (get_class($attribute->getInstance()) !== get_class($model)) {
					continue;
				}

				// Don't take attributes if mapped properties are not modified
				// But only for existing model (deleted model will not exists)
				if (!$model->isDirty($attribute->mapProperties) && $model->exists) {
					continue;
				}

				// Ask the attribute itself too
				if (false === $attribute->shouldTriggerRelatedUpdate($entityInstance, $model)) {
					continue;
				}

				// Check global callback
				if (is_callable($attributeCallback) && false === $attributeCallback($attribute, $entityInstance, $model)) {
					continue;
				}

				$attributes[$entityClass][] = $attribute;
			}
		}

		return array_filter($attributes);
	}
}
