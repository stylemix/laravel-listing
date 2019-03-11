<?php

namespace Stylemix\Listing\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Stylemix\Listing\Entity;
use Stylemix\Listing\IndexingListener;

trait ElasticSearchIndexing
{

	public function enableElasticSearchIndexing()
	{
		Entity::indexingInTests();
		IndexingListener::withExceptions();

		$this->afterApplicationCreated(function () {
			$prefix = Config::get('elasticquent.prefix') . '-test-';
			Config::set('elasticquent.prefix', $prefix);
			Artisan::call('entities:init', ['--force' => true]);
		});
	}
}
