<?php

namespace Stylemix\Listing\Attribute;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;
use Plank\Mediable\Media;
use Plank\Mediable\MediaUploader;
use Psr\Http\Message\StreamInterface;
use Stylemix\Listing\Contracts\Mutatable;
use Stylemix\Listing\Entity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @property mixed $toDisk Disk to save
 * @property mixed $toDirectory Directory to save
 * @property mixed $mimeTypes Strict to mime types
 */
class Attachment extends Base implements Mutatable
{
	protected static $syncQueue = [];

	public function __construct(string $name)
	{
		parent::__construct($name);
		$this->fillableName = $name . '_id';
	}

	/**
	 * Adds attribute mappings for elastic search
	 *
	 * @param \Illuminate\Support\Collection $mapping Mapping to modify
	 */
	public function elasticMapping($mapping)
	{
		$mapping[$this->name] = [
			'properties' => [
				'id' => ['type' => 'integer'],
				'disk' => ['type' => 'keyword'],
				'path' => ['type' => 'keyword'],
				'mime_type' => ['type' => 'keyword'],
				'aggregate_type' => ['type' => 'keyword'],
				'size' => ['type' => 'integer'],
			],
		];
	}

	public function getMutator(Entity $model, $key)
	{
		$attributes = $model->getAttributes();

		if ($key == $this->fillableName) {
			return Arr::get($attributes, $key);
		}

		return Arr::get($attributes, $key, function () use ($model) {
			return $this->getMedia($model);
		});
	}

	public function setMutator(Entity $model, $key, $value)
	{
		return $value;
	}

	/**
	 * @inheritdoc
	 */
	public function saving($data, $model)
	{
		if (!$model->isDirty($this->fillableName)) {
			return;
		}

		$value = Collection::make(Arr::wrap($data->get($this->fillableName)));

		$new = 0;

		foreach ($value as $i => $item) {
			if (is_numeric($item)) {
				$value[$i] = $model->getMedia($this->name)->find($item);
				continue;
			}

			if (!$this->isUploaded($item)) {
				continue;
			}

			$new ++;

			// Limit new uploads for single mode
			if (!$this->multiple && $new > 1) {
				$value->forget($i);
				continue;
			}

			$value[$i] = $this->uploadBuilder($item)->upload();
		}

		// Clear out all illegal types
		$value = $value->whereInstanceOf(Media::class);

		// Delete all attachments that don't exists on updated set
		if ($attachments = $model->getMedia($this->name)) {
			$attachments->diff($value)->each->delete();
		}

		$key = $this->name . ':' . ($model->getKey() ?? 'new');

		static::$syncQueue[$key]   = $value;
		$data[$this->fillableName] = $this->multiple ? array_values($value->modelKeys()) : $value->pluck('id')->first();
	}

	/**
	 * @inheritdoc
	 */
	public function saved($model)
	{
		$key = $this->name . ':' . ($model->wasRecentlyCreated ? 'new' : $model->getKey());

		if (!isset(static::$syncQueue[$key])) {
			return;
		}

		// Attach and set order of attachments
		$model->syncMedia(static::$syncQueue[$key], $this->name);

		// Clear for the next
		unset(static::$syncQueue[$key]);
	}

	/**
	 * @inheritdoc
	 */
	public function applyIndexData($data, $model)
	{
		$data[$this->name] = $this->getMedia($model);
	}

	/**
	 * @inheritdoc
	 */
	public function applyHydratingIndexData($data, $model)
	{
		if (!isset($data[$this->name])) {
			return;
		}

		if ($this->multiple) {
			$data[$this->name] = collect($data[$this->name])
				->mapInto(Fluent::class);
		}
		else {
			$data[$this->name] = new Fluent($data[$this->name]);
		}
	}

	/**
	 * Retrieve media for model
	 *
	 * @param \Stylemix\Listing\Entity $model
	 *
	 * @return array|mixed
	 */
	protected function getMedia(Entity $model)
	{
		$items = $model->getMedia($this->name)->map(function ($media) {
			return $this->getMediaJson($media);
		});

		return $this->multiple ? $items->all() : $items->first();
	}

	protected function getMediaJson($media)
	{
		return new Fluent([
			'id' => $media->id,
			'disk' => $media->disk,
			'path' => $media->directory . '/' . $media->filename . '.' . $media->extension,
			'mime_type' => $media->mime_type,
			'aggregate_type' => $media->aggregate_type,
			'size' => $media->size,
		]);
	}

	/**
	 * Sets directory generator by first and second symbol pairs from md5 hash of uploaded file
	 *
	 * @return $this
	 */
	public function toDirectoryByFilenameHash()
	{
		return $this->toDirectory(function ($attribute, $source) {
			$md5 = md5($this->getSourceFilename($source));
			return substr($md5, 0, 2) . '/' . substr($md5, 2, 2);
		});
	}

	/**
	 * Sets directory generator by current date
	 *
	 * @param string $format Directory format in PHP date format
	 *
	 * @return $this
	 */
	public function toDirectoryByDate($format = 'Y/m')
	{
		return $this->toDirectory(function ($attribute, $source) use ($format) {
			return now()->format($format);
		});
	}

	protected function isUploaded($item)
	{
		if ($item instanceof UploadedFile || $item instanceof File || $item instanceof StreamInterface || is_resource($item)) {
			return true;
		}

		if (!is_string($item)) {
			return false;
		}

		if (starts_with($item, 'http://') || starts_with($item, 'https://')) {
			return true;
		}

		if (starts_with($item, '/') && file_exists($item)) {
			return true;
		}

		return false;
	}

	protected function getSourceFilename($source)
	{
		if ($source instanceof File) {
			return $source->getFilename();
		}

		if (is_string($source)) {
			return $source;
		}

		return '';
	}

	/**
	 * Build media uploader and configure it
	 *
	 * @param mixed $item Upload source
	 *
	 * @return MediaUploader
	 */
	protected function uploadBuilder($item)
	{
		return app(MediaUploader::class)->fromSource($item)
			->toDisk($this->evaluate($this->toDisk ?? config('mediable.default_disk'), $item))
			->toDirectory($this->evaluate($this->toDirectory ?? '', $item))
			->setMaximumSize($this->evaluate($this->maximumSize ?? config('mediable.max_size'), $item))
			->onDuplicateIncrement();
	}

}
