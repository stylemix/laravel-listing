<?php

namespace Stylemix\Listing\Tests\Unit;

use Stylemix\Listing\Tests\Models\Book;
use Stylemix\Listing\Tests\TestCase;

class EntityDataTest extends TestCase
{

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testDataAttributeSaved()
    {
        $book = new Book();
        $book->forceFill([
			'title' => 'Title',
			'extra' => 'Extra',
		]);
        $book->save();

        $this->assertDatabaseHas($book->getTable(), ['title' => 'Title']);
        $this->assertDatabaseHas($book->dataAttributes()->getModel()->getTable(), [
        	'id' => $book->id, 'name' => 'extra', 'value' => 'Extra'
		]);
    }

}
