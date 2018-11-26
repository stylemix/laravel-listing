<?php

namespace Stylemix\Listing;

class IndexingListener
{

	function saved(Entity $model)
	{
		try {
			$model->addToIndex();
		}
		catch (\Exception $e) {
			report($e);
		}
	}

	function restored(Entity $model)
	{
		try {
			$model->addToIndex();
		}
		catch (\Exception $e) {
			report($e);
		}
	}

	function deleting(Entity $model)
	{
		$model->removeFromIndex();
	}

}
