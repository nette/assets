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
	private array $size;
	private float $duration;


	public function __construct(
		private readonly string $url,
		private readonly string $path,
	) {
	}


	/**
	 * Returns the publicly accessible URL.
	 */
	public function getUrl(): string
	{
		return $this->url;
	}


	/**
	 * Returns the absolute filesystem path to the source file.
	 */
	public function getPath(): string
	{
		return $this->path;
	}


	/**
	 * Allows direct echoing of the object to get the URL.
	 */
	public function __toString(): string
	{
		return $this->url;
	}


	/**
	 * Returns the width in pixels for image files.
	 * @throws \RuntimeException If the file is not a valid image or cannot be read.
	 */
	public function getWidth(): int
	{
		return $this->getSize()[0];
	}


	/**
	 * Returns the height in pixels for image files.
	 * @throws \RuntimeException If the file is not a valid image or cannot be read.
	 */
	public function getHeight(): int
	{
		return $this->getSize()[1];
	}


	/**
	 * Retrieves image dimensions [width, height]. Caches the result.
	 * @throws \RuntimeException If the file is not a valid image or cannot be read.
	 */
	private function getSize(): array
	{
		return $this->size ??= @getimagesize($this->getPath()) // @ - file may not exist or is not an image
			?: throw new \RuntimeException(sprintf(
				"Cannot get size of image '%s'. %s",
				$this->getPath(),
				Nette\Utils\Helpers::getLastError(),
			));
	}


	/**
	 * Returns the estimated duration in seconds for an MP3 audio file (assumes CBR).
	 * @throws \RuntimeException If the file is not a valid MP3 or cannot be read.
	 */
	public function getDuration(): float
	{
		return $this->duration ??= Helpers::guessMP3Duration($this->getPath());
	}
}
