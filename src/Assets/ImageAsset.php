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
		public readonly ?string $file = null,
		?int $width = null,
		?int $height = null,
		/** Alternative text for accessibility */
		public readonly ?string $alternative = null,
		public readonly bool $lazyLoad = false,
		public readonly int $density = 1,
		public readonly string|bool|null $crossorigin = null,
	) {
		if ($width === null && $height === null) {
			$this->lazyLoad(compact('width', 'height'), $this->getSize(...));
		} else {
			$this->width = $width;
			$this->height = $height;
		}
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
		[$this->width, $this->height] = $this->file && ([$w, $h] = getimagesize($this->file))
			? [(int) round($w / $this->density), (int) round($h / $this->density)]
			: [null, null];
	}
}
