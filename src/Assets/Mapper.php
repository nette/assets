<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Interface for asset mapper that can serve assets from various backends
 * like filesystem, CDN, cloud storage etc.
 */
interface Mapper
{
	/**
	 * Returns asset instance for given reference.
	 */
	public function getAsset(string $reference, array $options = []): ?Asset;
}
