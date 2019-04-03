{!! $phpOpenTag !!}

namespace App\Http\Forms;

use {{ $rootNamespace }}{{ str_replace('Form', '', $class) }};
use Stylemix\Base\FormResource;
use Stylemix\Listing\Facades\EntityForm;

class {{ $class }} extends FormResource
{

	/**
	 * List of field definitions defined by descendant class
	 *
	 * @return \Stylemix\Base\Fields\Base[]
	 */
	public function fields()
	{
		EntityForm::extend('title', function ($field) {
			return $field->required();
		});

		return EntityForm::forAttributes(
			{{ str_replace('Form', '', $class) }}::getAttributeDefinitions()->fillable()
		);
	}

}
