<?php

namespace Stylemix\Listing\Tests\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stylemix\Listing\EntityData;

class BookData extends EntityData
{

	protected $table = 'book_data';

	public function entity() : BelongsTo
	{
		return $this->belongsTo(Book::class, 'entity_id');
	}

}
