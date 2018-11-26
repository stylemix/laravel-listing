<?php

namespace Stylemix\Listing;

use Illuminate\Contracts\Queue\ShouldQueue;
use Stylemix\Listing\Facades\Entities;

class RelationListener implements ShouldQueue
{

	protected static $attributeCallback = null;

	/**
	 * Set function that checks attribute.
	 * If false is returned then the attribute will not used for reindexing.
	 * Callback receives:<br>
	 * - (1) attribute instance being processed<br>
	 * - (2) owner entity instance of the attribute
	 * - (3) model instance triggered the event
	 *
	 * @param callable $attributeCallback
	 */
	public static function setAttributeCallback($attributeCallback)
	{
		self::$attributeCallback = $attributeCallback;
	}

	public function updated(Entity $entity)
	{
		$relatedAttributes = Entities::getEntityRelatedAttributes($entity, static::$attributeCallback);

		foreach ($relatedAttributes as $entityClass => $attributes) {
			/** @var \Stylemix\Listing\Attribute\Relation $attribute */
			foreach ($attributes as $attribute) {
				$query = $entityClass::search()
					->where($attribute->name, $entity->getOriginal($attribute->getOtherKey()) ?? $entity->getAttribute($attribute->getOtherKey()))
					->setSource(['id']);

				$query->chunk(30, function ($results) use ($entity, $query) {
					// $results argument contains only ids of records.
					// To force resolving all related attributes
					// we should take results from db where only raw data is stored
					$results = $query->getEntity()
						->whereIn('id', $results->modelKeys())
						->get();

					foreach ($results as $model) {
						try {
							$model->addToIndex();
						}
						catch (\Exception $e) {
						}
					}
				});
			}
		}
	}

	public function deleted(Entity $entity)
	{
		$relatedAttributes = Entities::getEntityRelatedAttributes($entity, static::$attributeCallback);

		$entityKey = $entity->getOriginal($entity->getKeyName()) ?? $entity->getKey();

		foreach ($relatedAttributes as $entityClass => $attributes) {
			// Since this update should not trigger the listeners that end app registers
			// replace event dispatcher with another one that holds native events only
			$originalDispatcher = $entityClass::getEventDispatcher();
			$entityClass::flushEventListeners();
			$entityClass::registerPrimaryListeners();

			/** @var \Stylemix\Listing\Attribute\Relation $attribute */
			foreach ($attributes as $attribute) {
				$query = $entityClass::search()
					->where($attribute->name, $entity->getOriginal($attribute->getOtherKey()) ?? $entity->getAttribute($attribute->getOtherKey()))
					->setSource(['id']);

				$query->chunk(30, function ($results) use ($entity, $query, $attribute, $entityKey) {
					// $results argument contains only ids of records.
					// To force resolving all related attributes
					// we should take results from db where only raw data is stored
					$results = $query->getEntity()
						->whereIn('id', $results->modelKeys())
						->get();

					foreach ($results as $model) {
						try {
							if ($attribute->multiple) {
								$model[$attribute->fillableName] = array_diff(array_wrap($model[$attribute->fillableName]), [$entityKey]);
							}
							else {
								$model[$attribute->fillableName] = null;
							}

							$model->save();
						}
						catch (\Exception $e) {
						}
					}
				});
			}

			// Set back original dispatcher to make further events handled by app
			$entityClass::setEventDispatcher($originalDispatcher);
		}
	}

}
