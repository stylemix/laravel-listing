<?php

namespace Stylemix\Listing\Tests\Unit;

use Stylemix\Listing\Tests\Models\Book;
use Stylemix\Listing\Tests\TestCase;

class EntityTest extends TestCase
{

	public function testWithoutEvents()
	{
		$this->assertCount(2, $this->getListeners());

		Book::withoutEvents(function () {
			$this->assertCount(1, $this->getListeners());
		});

		$this->assertCount(2, $this->getListeners());
	}

	protected function getListeners() : array
	{
		// Initiate model boot
		new Book();

		/** @var \Illuminate\Events\Dispatcher $events */
		$events    = Book::getEventDispatcher();
		$listeners = $events->getListeners('eloquent.saved: ' . Book::class);

		return $listeners;
	}
}
