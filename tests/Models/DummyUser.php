<?php

namespace Stylemix\Listing\Tests\Models;

use Stylemix\Listing\Attribute\Email;
use Stylemix\Listing\Attribute\Id;
use Stylemix\Listing\Attribute\Text;
use Stylemix\Listing\Entity;

class DummyUser extends Entity
{

	/**
	 * Attribute definitions
	 *
	 * @return array
	 */
	protected static function attributeDefinitions() : array
	{
		return [
			Id::make(),
			Text::make('name'),
			Email::make('email'),
		];
	}
}
