<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Interface representing a static asset like image, script, stylesheet etc.
 * Provides basic properties and operations for working with the asset.
 */
interface Asset
{
	/**
	 * Returns the public URL of the asset.
	 */
	public function getUrl(): string;

	/**
	 * Shortcut for getUrl()
	 */
	public function __toString(): string;

	/**
	 * Checks if the asset file exists in the filesystem.
	 */
	public function exists(): bool;
}
