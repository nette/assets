<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Image asset.
 */
class ImageAsset implements Asset
{
	use LazyLoad;

	public readonly ?int $width;
	public readonly ?int $height;


	public function __construct(
		public readonly string $url,
		public readonly ?string $mimeType = null,
		public readonly ?string $sourcePath = null,
		?int $width = null,
		?int $height = null,
		/** Alternative text for accessibility */
		public readonly ?string $alternative = null,
		public readonly bool $lazyLoad = false,
	) {
		$this->lazyLoad(['width' => null, 'height' => null], $this->getSize(...));
	}


	public function __toString(): string
	{
		return $this->url;
	}


	/**
	 * Retrieves image dimensions.
	 */
	private function getSize(): void
	{
		[$this->width, $this->height] = $this->sourcePath ? getimagesize($this->sourcePath) : null;
	}
}
