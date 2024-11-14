<?php

declare(strict_types=1);

namespace Nette\Assets;

use Nette;


/**
 * A file-based asset implementation that provides additional functionality for images and audio files.
 * Supports getting dimensions of images and duration of audio files.
 */
class FileAsset implements Asset
{
	private array $size;
	private int $duration;


	public function __construct(
		private readonly string $url,
		private readonly string $sourcePath,
	) {
	}


	/**
	 * Returns the public URL of the asset.
	 */
	public function getUrl(): string
	{
		return $this->url;
	}


	/**
	 * Returns the filesystem path to the source asset file.
	 */
	public function getSourcePath(): string
	{
		return $this->sourcePath;
	}


	/**
	 * Shortcut for getUrl()
	 */
	public function __toString(): string
	{
		return $this->url;
	}


	/**
	 * Checks if the asset file exists in the filesystem.
	 */
	public function exists(): bool
	{
		return is_file($this->sourcePath);
	}


	/**
	 * Returns duration in seconds for MP3 audio file.
	 */
	public function getDuration(): int
	{
		return $this->duration ??= Helpers::getMP3Duration($this->getSourcePath());
	}


	/**
	 * Returns width in pixels for image files.
	 */
	public function getWidth(): int
	{
		return $this->getSize()[0];
	}


	/**
	 * Returns height in pixels for image files.
	 */
	public function getHeight(): int
	{
		return $this->getSize()[1];
	}


	/**
	 * Returns the dimensions [width, height] of an image file.
	 * @throws \RuntimeException if file is not an image or doesn't exist
	 */
	private function getSize(): array
	{
		return $this->size ??= @getimagesize($this->getSourcePath()) // @ - file may not exist or is not an image
			?: throw new \RuntimeException(sprintf(
				"Cannot get size of image '%s'. %s",
				$this->getSourcePath(),
				Nette\Utils\Helpers::getLastError(),
			));
	}
}
