<?php

namespace Stylemix\Listing\Tests;

use Plank\Mediable\Media;
use Stylemix\Listing\Facades\Entities;
use Stylemix\Listing\ServiceProvider;
use Stylemix\Listing\Tests\Models\DummyBook;
use Stylemix\Listing\Tests\Models\DummyUser;

class TestCase extends \Orchestra\Testbench\TestCase
{
	/**
	 * Setup the test environment.
	 */
	protected function setUp()
	{
		parent::setUp();

		$this->loadMigrationsFrom(__DIR__ . '/database/migrations');
		$this->withFactories(__DIR__ . '/database/factories');

		Entities::register('user', DummyUser::class);
		Entities::register('book', DummyBook::class);
	}

	protected function getPackageProviders($app)
	{
		return [ServiceProvider::class];
	}
	/**
	 * Define environment setup.
	 *
	 * @param  \Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function getEnvironmentSetUp($app)
	{
		// Setup default database to use sqlite :memory:
		$app['config']->set('database.default', 'testing');
		$app['config']->set('database.connections.testing', [
			'driver'   => 'sqlite',
			'database' => ':memory:',
			'prefix'   => '',
		]);
		$app['config']->set('elasticquent', [
			'config' => [
				'hosts' => [
					env('ELASTICSEARCH_HOST', 'localhost:9200'),
				],
				'retries' => 1,
			],
		]);

		// Support mediable
		$app['config']->set('mediable.model', Media::class);
	}

}
