<?php

namespace Stylemix\Listing;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider as BaseProvider;
use Stylemix\Generators\Commands\CrudCommand;
use Stylemix\Listing\Attribute\Attachment;
use Stylemix\Listing\Attribute\Boolean;
use Stylemix\Listing\Attribute\Currency;
use Stylemix\Listing\Attribute\Date;
use Stylemix\Listing\Attribute\Email;
use Stylemix\Listing\Attribute\Enum;
use Stylemix\Listing\Attribute\Id;
use Stylemix\Listing\Attribute\Keyword;
use Stylemix\Listing\Attribute\Location;
use Stylemix\Listing\Attribute\LongText;
use Stylemix\Listing\Attribute\Numeric;
use Stylemix\Listing\Attribute\Price;
use Stylemix\Listing\Attribute\Relation;
use Stylemix\Listing\Attribute\Text;
use Stylemix\Listing\Attribute\Url;
use Stylemix\Listing\Console\EntitiesIndexCommand;
use Stylemix\Listing\Console\EntitiesInitCommand;
use Stylemix\Listing\Console\GenerateEntityCommand;
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

        $this->mergeConfigFrom(__DIR__ . '/../config/generator_stubs.php', 'generator_stubs');
    }

    /**
     * Boot the package.
     */
    public function boot()
    {
    	// Macro function for using in migration schemas
		// that adds entity related columns
    	Blueprint::macro('entityColumns', function () {
			$this->timestamp('indexed_at')->nullable()->index();
		});

		if ($this->app->runningInConsole()) {
			$this->commands([
				EntitiesInitCommand::class,
				EntitiesIndexCommand::class,
			]);

			// Generators package may only be installed on dev mode
			// Check for class existence to register the command
			if (class_exists(CrudCommand::class)) {
				$this->commands(GenerateEntityCommand::class);
			}
		}

		EntityForm::register(Numeric::class, function (Numeric $attribute) {
			return \Stylemix\Base\Fields\NumberField::make($attribute->fillableName)
				->required($attribute->required)
				->multiple($attribute->multiple)
				->label($attribute->label);
		});

		EntityForm::register(Id::class, function () {
			return null;
		});

		EntityForm::register(Boolean::class, function (Boolean $attribute) {
			return \Stylemix\Base\Fields\CheckboxField::make($attribute->fillableName)
				->required($attribute->required)
				->label($attribute->label);
		});

		EntityForm::register(Currency::class, function (Currency $attribute) {
			return \Stylemix\Base\Fields\NumberField::make($attribute->fillableName)
				->required($attribute->required)
				->min(0)
				->multiple($attribute->multiple)
				->label($attribute->label);
		});

		EntityForm::register(Price::class, function (Price $attribute) {
			return [
				\Stylemix\Base\Fields\NumberField::make($attribute->fillableName)
					->required($attribute->required)
					->min(0)
					->multiple($attribute->multiple)
					->label($attribute->label),
				\Stylemix\Base\Fields\NumberField::make($attribute->saleName)
					->rules('nullable')
					->min(0)
					->multiple($attribute->multiple)
					->label($attribute->saleLabel)
			];
		});

		EntityForm::register(Email::class, function (Keyword $attribute) {
			return \Stylemix\Base\Fields\EmailField::make($attribute->fillableName)
				->required($attribute->required)
				->multiple($attribute->multiple)
				->label($attribute->label);
		});

		EntityForm::register(Keyword::class, function (Keyword $attribute) {
			return \Stylemix\Base\Fields\TextField::make($attribute->fillableName)
				->required($attribute->required)
				->multiple($attribute->multiple)
				->label($attribute->label);
		});

		EntityForm::register(Url::class, function (Keyword $attribute) {
			return \Stylemix\Base\Fields\TextField::make($attribute->fillableName)
				->typeUrl()
				->rules(['url'])
				->required($attribute->required)
				->multiple($attribute->multiple)
				->label($attribute->label);
		});

		EntityForm::register(Date::class, function (Date $attribute) {
			return \Stylemix\Base\Fields\DatetimeField::make($attribute->fillableName)
				->required($attribute->required)
				->multiple($attribute->multiple)
				->label($attribute->label);
		});

		EntityForm::register(Enum::class, function (Enum $attribute) {
			return \Stylemix\Base\Fields\SelectField::make($attribute->fillableName)
				->required($attribute->required)
				->multiple($attribute->multiple)
				->options($attribute->getSelectOptions())
				->label($attribute->label);
		});

		EntityForm::register(Text::class, function (Text $attribute) {
			return \Stylemix\Base\Fields\TextField::make($attribute->fillableName)
				->required($attribute->required)
				->multiple($attribute->multiple)
				->label($attribute->label);
		});

		EntityForm::register(LongText::class, function (LongText $attribute) {
			if ($attribute->editor) {
				return \Stylemix\Base\Fields\EditorField::make($attribute->fillableName)
					->placeholder($attribute->placeholder)
					->required($attribute->required)
					->multiple($attribute->multiple)
					->label($attribute->label);
			}

			return \Stylemix\Base\Fields\TextareaField::make($attribute->fillableName)
				->placeholder($attribute->placeholder)
				->required($attribute->required)
				->multiple($attribute->multiple)
				->label($attribute->label);
		});

		EntityForm::register(Attachment::class, function (Attachment $attribute) {
			return \Stylemix\Base\ExtraFields\AttachmentField::make($attribute->fillableName)
				->multiple($attribute->multiple)
				->required($attribute->required)
				->mimeTypes($attribute->mimeTypes)
				->label($attribute->label)
				->mediaTag($attribute->name);
		});

		EntityForm::register(Relation::class, function (Relation $attribute) {
			return \Stylemix\Base\ExtraFields\RelationField::make($attribute->fillableName)
				->setQuery($attribute->getQueryBuilder())
				->required($attribute->required)
				->multiple($attribute->multiple)
				->label($attribute->label)
				->related($attribute->related)
				->otherKey($attribute->getOtherKey());
		});

		EntityForm::register(Location::class, function (Location $attribute) {
			return \Stylemix\Base\ExtraFields\LocationField::make($attribute->fillableName)
				->required($attribute->required)
				->multiple($attribute->multiple)
				->label($attribute->label);
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
