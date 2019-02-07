<?php

namespace Stylemix\Listing;

class IndexingListener
{
	protected static $withExceptions = false;

	/**
	 * Triggered after each model save
	 *
	 * @param \Stylemix\Listing\Entity $model
	 *
	 * @throws \Exception
	 */
	function saved(Entity $model)
	{
		try {
			$model->addToIndex();
		}
		catch (\Exception $e) {
			if (static::$withExceptions) {
				throw $e;
			}
			else {
				report($e);
			}
		}
	}

	/**
	 * Triggered after each model restoration
	 *
	 * @param \Stylemix\Listing\Entity $model
	 *
	 * @throws \Exception
	 */
	function restored(Entity $model)
	{
		try {
			$model->addToIndex();
		}
		catch (\Exception $e) {
			if (static::$withExceptions) {
				throw $e;
			}
			else {
				report($e);
			}
		}
	}

	/**
	 * Triggered after before each model deleting
	 *
	 * @param \Stylemix\Listing\Entity $model
	 *
	 * @throws \Exception
	 */
	function deleting(Entity $model)
	{
		try {
			$model->removeFromIndex();
		}
		catch (\Exception $e) {
			if (static::$withExceptions) {
				throw $e;
			}
			else {
				report($e);
			}
		}
	}

	/**
	 * Enable throwing exceptions in indexing operations
	 *
	 * @param bool $flag
	 */
	public static function withExceptions($flag = true)
	{
		self::$withExceptions = $flag;
	}

}
