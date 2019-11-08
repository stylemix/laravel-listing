<?php

namespace Stylemix\Listing\Tests\Unit;

use Stylemix\Listing\Facades\Entities;
use Stylemix\Listing\Tests\Models\DummyBook;
use Stylemix\Listing\Tests\TestCase;

class EntityManagerTest extends TestCase
{

	public function testRegister()
	{
		$this->assertInstanceOf(DummyBook::class, Entities::make('book'));
		$this->assertInstanceOf(DummyBook::class, Entities::make(DummyBook::class));
		$this->assertEquals('book', Entities::getAlias(DummyBook::class));
		$this->assertEquals(DummyBook::class, Entities::modelClass('book'));
	}
}
