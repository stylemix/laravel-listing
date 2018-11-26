<?php

namespace Stylemix\Listing\Facades;

use Illuminate\Support\Facades\Facade;
use Stylemix\Listing\EntityManager;

/**
 * @method static entity($class, $name = null) Registers entity class
 * @method static \Stylemix\Listing\Entity make($entity) Creates instance of entity class
 * @method static resetIndexed($entity) Reset indexed flag for all entity records in DB
 * @method static mixed indexingStatus($entity, $value = null) Get or set reindexing status for entity
 * @method static mixed remapStatus($entity, $value = null) Get or set remap status for entity
 */
class Entities extends Facade
{

    protected static function getFacadeAccessor()
    {
        return EntityManager::class;
    }

}
