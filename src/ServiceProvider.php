<?php

namespace Stylemix\Listing;

use Illuminate\Support\ServiceProvider as BaseProvider;
use Stylemix\Listing\Attribute\Attachment;
use Stylemix\Listing\Attribute\Boolean;
use Stylemix\Listing\Attribute\Currency;
use Stylemix\Listing\Attribute\Date;
use Stylemix\Listing\Attribute\Enum;
use Stylemix\Listing\Attribute\Id;
use Stylemix\Listing\Attribute\Keyword;
use Stylemix\Listing\Attribute\LongText;
use Stylemix\Listing\Attribute\Numeric;
use Stylemix\Listing\Attribute\Price;
use Stylemix\Listing\Attribute\Relation;
use Stylemix\Listing\Attribute\Text;
use Stylemix\Listing\Facades\EntityForm;

class ServiceProvider extends BaseProvider
{

    /**
     * Register IoC bindings.
     */
    public function register()
    {
        // Bind the manager as a singleton on the container.
        $this->app->singleton(EntityManager::class, function ($app) {
            return EntityManager::getInstance();
        });

        // Bind the Form builder as a singleton on the container.
        $this->app->singleton(Form::class, function ($app) {
            return new Form();
        });
    }

    /**
     * Boot the package.
     */
    public function boot()
    {
		EntityForm::register(Numeric::class, function (Numeric $attribute) {
			return \Stylemix\Base\Fields\Number::make($attribute->fillableName)
				->required($attribute->required)
				->multiple($attribute->multiple)
				->label($attribute->label);
		});

		EntityForm::register(Id::class, function () {
			return null;
		});

		EntityForm::register(Boolean::class, function (Boolean $attribute) {
			return \Stylemix\Base\Fields\Checkbox::make($attribute->fillableName)
				->required($attribute->required)
				->label($attribute->label);
		});

		EntityForm::register(Currency::class, function (Currency $attribute) {
			return \Stylemix\Base\Fields\Number::make($attribute->fillableName)
				->min(0)
				->multiple($attribute->multiple)
				->label($attribute->label);
		});

		EntityForm::register(Price::class, function (Price $attribute) {
			return [
				\Stylemix\Base\Fields\Number::make($attribute->fillableName)
					->min(0)
					->multiple($attribute->multiple)
					->label($attribute->label),
				\Stylemix\Base\Fields\Number::make($attribute->saleName)
					->rules('nullable')
					->min(0)
					->multiple($attribute->multiple)
					->label($attribute->saleLabel)
			];
		});

		EntityForm::register(Keyword::class, function (Keyword $attribute) {
			return \Stylemix\Base\Fields\Input::make($attribute->fillableName)
				->required($attribute->required)
				->multiple($attribute->multiple)
				->label($attribute->label);
		});

		EntityForm::register(Date::class, function (Date $attribute) {
			return \Stylemix\Base\Fields\Datetime::make($attribute->fillableName)
				->required($attribute->required)
				->multiple($attribute->multiple)
				->label($attribute->label);
		});

		EntityForm::register(Enum::class, function (Enum $attribute) {
			return \Stylemix\Base\Fields\Select::make($attribute->fillableName)
				->required($attribute->required)
				->multiple($attribute->multiple)
				->options($attribute->getSelectOptions())
				->label($attribute->label);
		});

		EntityForm::register(Text::class, function (Text $attribute) {
			return \Stylemix\Base\Fields\Input::make($attribute->fillableName)
				->required($attribute->required)
				->multiple($attribute->multiple)
				->label($attribute->label);
		});

		EntityForm::register(LongText::class, function (LongText $attribute) {
			return \Stylemix\Base\Fields\Textarea::make($attribute->fillableName)
				->placeholder($attribute->placeholder)
				->required($attribute->required)
				->multiple($attribute->multiple)
				->label($attribute->label);
		});

		EntityForm::register(Attachment::class, function (Attachment $attribute) {
			return Fields\AttachmentField::make($attribute->fillableName)
				->multiple($attribute->multiple)
				->required($attribute->required)
				->mimeTypes($attribute->mimeTypes)
				->label($attribute->label)
				->mediaTag($attribute->name);
		});

		EntityForm::register(Relation::class, function (Relation $attribute) {
			return Fields\RelationField::make($attribute->fillableName)
				->attributeInstance($attribute)
				->required($attribute->required)
				->multiple($attribute->multiple)
				->label($attribute->label)
				->related($attribute->related)
				->otherKey($attribute->getOtherKey());
		});

    }

    /**
     * Which IoC bindings the provider provides.
     *
     * @return array
     */
    public function provides()
    {
        return array(
			EntityManager::class,
			Form::class
        );
    }
}
