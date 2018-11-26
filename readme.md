### Define attributes

In model file in function `attributeDefinitions()`:

```php
use Stylemix\Listing\Attribute\Text;
use Stylemix\Listing\Attribute\Currency;

//...

public static function attributeDefinitions() : array
{
	return [
		Currency::make('price'),
		Text::make('description'),
	];
}
```