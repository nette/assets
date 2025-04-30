<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Generic asset for any general file type.
 */
class GenericAsset implements Asset
{
	public function __construct(
		public readonly string $url,
		public readonly ?string $sourcePath = null,
	) {
	}


	public function __toString(): string
	{
		return $this->url;
	}
}
