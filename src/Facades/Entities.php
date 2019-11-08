<?php

namespace Stylemix\Listing\Facades;

use Illuminate\Support\Facades\Facade;
use Stylemix\Listing\EntityManager;

/**
 * @method static createDataTable($base) Create schema for entity data table
 * @method static dropDataTable($base) Drop entity data table
 * @method static register($alias, $class) Registers entity class
 * @method static \Stylemix\Listing\Entity make($entity) Creates instance of entity class
 * @method static string modelClass($alias) Get entity class by alias
 * @method static string getAlias($class) Get entity alias by class name
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
