<?php

namespace Stylemix\Listing\Console;

use Illuminate\Console\Command;
use Stylemix\Listing\Facades\Entities;

class EntitiesInitCommand extends Command
{

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'entities:init {--force}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Run initialization for all entities.';


	/**
	 * Create a new command instance.
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		foreach (Entities::getBindings() as $entity => $class) {
			/** @var \Stylemix\Listing\Entity $model */
			$model = Entities::make($entity);

			if ($model::indexExists()) {
				if (!$this->option('force')) {
					$this->warn("Elastic Search index exists: <info>{$model->getIndexName()}</info>. Use <comment>--force</comment> option to override existing index.");
					continue;
				}

				$model::deleteIndex();
				$this->warn("Elastic Search index deleted: <info>{$model->getIndexName()}</info>");
			}

			$model::createIndex();
			$this->info("Elastic Search index created successfully: <info>{$model->getIndexName()}</info>.");
		}
	}

}
