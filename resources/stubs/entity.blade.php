{!! $phpOpenTag !!}

namespace {{ $namespace }};

use Illuminate\Database\Eloquent\Relations\HasMany;
@if ($softDeletes)
use Illuminate\Database\Eloquent\SoftDeletes;
@endif
use Stylemix\Listing\Attribute\Boolean;
use Stylemix\Listing\Attribute\Date;
use Stylemix\Listing\Attribute\Enum;
use Stylemix\Listing\Attribute\Id;
use Stylemix\Listing\Attribute\Image;
use Stylemix\Listing\Attribute\Location;
use Stylemix\Listing\Attribute\LongText;
use Stylemix\Listing\Attribute\Price;
use Stylemix\Listing\Attribute\Relation;
use Stylemix\Listing\Attribute\Slug;
use Stylemix\Listing\Attribute\Text;
use Stylemix\Listing\Entity;

/**
 * Class {{ $class }}
 * @mixin \Eloquent
 */
class {{ $class }} extends Entity
{
@if ($softDeletes)
    use SoftDeletes;
@endif

@foreach ($relations as $relation)
	public function {{ $relation->name }}()
	{
		{!! $relation->relationCode !!}
	}

@endforeach
	public static function attributeDefinitions() : array
	{
		return [
			Id::make(),
			Text::make('title')
				->fillable()
				->search(['boost' => 3]),
			Date::make('created_at'),
			Date::make('updated_at'),
			// declare your custom attributes here
		];
	}
}
