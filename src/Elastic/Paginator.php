<?php

namespace Stylemix\Listing\Elastic;

use Elasticquent\ElasticquentPaginator;

class Paginator extends ElasticquentPaginator
{

	public function __construct($items, $hits, $total, $perPage, $currentPage = null, array $options = [])
	{
		parent::__construct($items, $hits, $total, $perPage, $currentPage, $options);

		$this->lastPage = min(floor(100000 / $perPage), $this->lastPage);
	}
}
