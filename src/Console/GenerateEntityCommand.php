<?php

namespace Stylemix\Listing\Console;

use Stylemix\Generators\Commands\CrudCommand;

class GenerateEntityCommand extends CrudCommand
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $name = 'generate:entity';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Generates full CRUD for entity resource (Model, Migration, Controller, etc.)';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		$this->resource = $this->argument('resource');
		$this->settings = config('generators.defaults');

		$this->callModel();
		$this->callFactory();
		$this->callFormResource();
		$this->callRequest();
		$this->callPolicy();
		$this->callResource();
		$this->callController();
		$this->callAdmin();
		$this->callMigration();
		$this->callSeeder();
		$this->callTestCrud();
		$this->callMigrate();

		$this->info('All Done! Just add the following to your AppServiceProvider.php in register() method:');
		$this->table([], [["Entities::entity(\\{$this->getAppNamespace()}{$this->getModelName()}::class, '{$this->getResourceName()}');"]]);
		$this->info('and run initialization command:');
		$this->table([], [["php artisan entities:init"]]);
	}

	protected function callCommand($command, $name, $options = [])
	{
		switch ($command) {
		case 'model':
			$options['--stub'] = 'entity';
			break;
		case 'form':
			$options['--stub'] = 'entity_form';
			break;
		case 'controller':
			$options['--stub'] = 'entity_controller';
			break;
		case 'migration':
			$options['--stub'] = 'entity_migration';
			break;
		case 'test':
			$options['--stub'] = 'entity_test_crud';
			break;
		}

		parent::callCommand($command, $name, $options);
	}
}
