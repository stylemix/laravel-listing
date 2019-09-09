<?php

namespace Stylemix\Listing\Tests\Unit;

use Stylemix\Listing\Tests\Models\DummyBook;
use Stylemix\Listing\Tests\TestCase;

class EntityTest extends TestCase
{

	public function testWithoutEvents()
	{
		$this->assertCount(2, $this->getListeners());

		DummyBook::withoutEvents(function () {
			$this->assertCount(1, $this->getListeners());
		});

		$this->assertCount(2, $this->getListeners());
	}

	protected function getListeners() : array
	{
		// Initiate model boot
		new DummyBook();

		/** @var \Illuminate\Events\Dispatcher $events */
		$events    = DummyBook::getEventDispatcher();
		$listeners = $events->getListeners('eloquent.saved: ' . DummyBook::class);

		return $listeners;
	}
}
