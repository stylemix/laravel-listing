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
        $this->assertDatabaseHas($book->getTable() . '_data', [
        	'id' => $book->id, 'name' => 'extra', 'value' => 'Extra'
		]);
    }

	public function testDataHydrated()
	{
		$book = new Book();
		$book->forceFill([
			'title' => 'Title',
			'extra' => 'Extra',
		]);
		$book->save();

		$fresh = $book->fresh();
		$this->assertEquals('Extra', $fresh->extra);
	}

	public function testDataUpdated()
	{
		$book = Book::forceCreate([
			'title' => 'Title',
			'extra' => 'Extra',
		]);

		$book = $book->fresh();
		$book->extra = 'Extra2';
		$book->save();

		$fresh = $book->fresh();
		$this->assertEquals('Title', $fresh->title);
		$this->assertEquals('Extra2', $fresh->extra);
	}

}
