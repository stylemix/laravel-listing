<?php

use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

/**
 * @var $factory \Illuminate\Database\Eloquent\Factory
 */

$factory->define(\Stylemix\Listing\Tests\Models\DummyBook::class, function (Faker $faker) {
    return [
        'title' => $faker->words(3, true),
        'enum' => $faker->randomElement(\Stylemix\Listing\Tests\Models\DummyEnum::values()),
    ];
});
