<?php

namespace Stylemix\Listing\Console;

use Illuminate\Console\Command;
use Stylemix\Listing\Facades\Entities;

class EntitiesIndexCommand extends Command
{

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'entities:index {entity} {--force} {--remap}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Reindex all entity data to ElasticSearch.';


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
		/** @var \Stylemix\Listing\Entity $model */
		$entity = $this->argument('entity');
		$model  = Entities::make($entity);

		if ($this->option('remap')) {
			if ($model::indexExists()) {
				$this->info("Elastic Search index deleted: {$model->getIndexName()}");
				$model::deleteIndex();
			}

			$model::createIndex();
			$this->info('Elastic Search index created successfully.');
			Entities::remapStatus($entity, false);
			Entities::resetIndexed($entity);
		}

		Entities::indexingStatus($entity, true);

		$builder = $model->query();

		if (!$this->option('force')) {
			$builder->whereNull('indexed_at');
		}

		$total   = $builder->count();
		$failed  = 0;
		$bar     = $this->getOutput()->createProgressBar($total);

		$builder->chunk(100, function ($results) use ($bar, &$failed) {
			$results->each(function (\Stylemix\Listing\Entity $model) use ($bar, &$failed) {
				$model->removeFromIndex();

				try {
					$model->addToIndex();
					$bar->advance();
				}
				catch (\Exception $e) {
					$failed ++;
					report($e);
				}
			});
		});

		Entities::indexingStatus($entity, false);

		$bar->finish();
		$this->line('');
		$this->comment('Re-indexed ' . ($total - $failed) . ' records');
		$this->comment('Failed ' . $failed . ' records');
	}

}
