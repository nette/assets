<?php

declare(strict_types=1);

namespace Nette\Assets;

use Nette;


/**
 * Represents an asset backed by a local file.
 * Supports getting dimensions of images and duration of audio files.
 */
class FileAsset implements Asset
{
	use LazyLoad;

	public readonly ?int $width;
	public readonly ?int $height;
	public readonly ?float $duration;


	public function __construct(
		public readonly string $url,
		public readonly ?string $file = null,
	) {
		$this->lazyLoad(['width' => null, 'height' => null], $this->getSize(...));
		$this->lazyLoad(['duration' => null], fn() => $this->duration = $this->file
			? Helpers::guessMP3Duration($this->file)
			: null);
	}


	/**
	 * Allows direct echoing of the object to get the URL.
	 */
	public function __toString(): string
	{
		return $this->url;
	}


	/**
	 * Retrieves image dimensions [width, height]. Caches the result.
	 * @throws \RuntimeException If the file is not a valid image or cannot be read.
	 */
	private function getSize(): void
	{
		[$this->width, $this->height] = @getimagesize($this->file) // @ - file may not exist or is not an image
			?: throw new \RuntimeException(sprintf(
				"Cannot get size of image '%s'. %s",
				$this->file,
				Nette\Utils\Helpers::getLastError(),
			));
	}
}
