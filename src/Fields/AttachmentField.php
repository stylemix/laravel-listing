<?php

namespace Stylemix\Listing\Fields;

use Illuminate\Http\Request;
use Plank\Mediable\Media;
use Stylemix\Base\Fields\Base;

/**
 * @property string $mediaTag
 */
class AttachmentField extends Base
{

	public $component = 'attachment-field';

	protected function fillAttributeFromRequest(Request $request, $requestAttribute, $model, $attribute)
	{
		$requestAttribute = $requestAttribute ?: $attribute;

		if ($request->exists($requestAttribute)) {
			$files = array_wrap($request->request->get($requestAttribute, []));
			$files += array_wrap($request->files->get($requestAttribute, []));
			ksort($files);
			$model->{$attribute} = $files;
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function resolveAttribute($resource, $attribute)
	{
		if (!method_exists($resource, 'getMedia')) {
			throw new \Exception('Attachment field can not be resolved to resource that do not uses media attachments');
		}

		$attached = $resource->getMedia($this->mediaTag)->map(function ($media) {
			return $this->getMediaJson($media);
		});

		if (!$this->multiple) {
			$attached = $attached->first();
		}

		$this->attached = $attached;

		return parent::resolveAttribute($resource, $attribute);
	}

	protected function getMediaJson(Media $media)
	{
		return (object) [
			'id' => $media->id,
			'url' => $media->getUrl(),
			'disk' => $media->disk,
			'directory' => $media->directory,
			'filename' => $media->filename . '.' . $media->extension,
			'mime_type' => $media->mime_type,
			'aggregate_type' => $media->aggregate_type,
			'size' => $media->size,
		];
	}

}
