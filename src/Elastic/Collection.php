<?php

namespace Stylemix\Listing\Elastic;

use Elasticquent\ElasticquentResultCollection;

/**
 * Class Collection
 *
 * @method \Stylemix\Listing\Elastic\Aggregations getAggregations()
 */
class Collection extends ElasticquentResultCollection
{

	protected $scrollId;

	protected $perPage;

	public function __construct($items = [], $meta = null)
	{
		parent::__construct($items, $meta);
	}

	public function setMeta(array $meta)
	{
		$this->scrollId = isset($meta['_scroll_id']) ? $meta['_scroll_id'] : null;
		$this->perPage  = isset($meta['_per_page']) ? $meta['_per_page'] : null;

		return parent::setMeta($meta);
	}

	/**
	 * Get current scroll id if was requested
	 *
	 * @return string
	 */
	public function getScrollId()
	{
		return $this->scrollId;
	}

	/**
	 * Paginate Collection
	 *
	 * @param int $pageLimit
	 *
	 * @return \Stylemix\Listing\Elastic\Paginator
	 */
	public function paginate($pageLimit = null)
	{
		$page = Paginator::resolveCurrentPage() ?: 1;

		if (!$pageLimit && $this->perPage) {
			$pageLimit = $this->perPage;
		}

		return new Paginator($this->items, $this->hits, $this->totalHits(), $pageLimit, $page, ['path' => Paginator::resolveCurrentPath()]);
	}

}
