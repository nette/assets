<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Represents a static asset (image, script, stylesheet, etc.).
 * Provides a way to retrieve its public URL.
 */
interface Asset
{
	/**
	 * Returns the public URL of the asset.
	 */
	public function getUrl(): string;

	/**
	 * Allows direct echoing of the object to get the URL.
	 */
	public function __toString(): string;
}
