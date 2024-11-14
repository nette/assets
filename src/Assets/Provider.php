<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Interface for asset providers that can serve assets from various backends
 * like filesystem, CDN, cloud storage etc.
 */
interface Provider
{
	/**
	 * Returns asset instance for given path.
	 */
	public function getAsset(string $path, array $options = []): Asset;
}
