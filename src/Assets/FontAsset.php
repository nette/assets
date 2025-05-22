<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Font asset.
 */
class FontAsset implements Asset
{
	public function __construct(
		public readonly string $url,
		public readonly ?string $mimeType = null,
		public readonly ?string $file = null,
		/** SRI integrity hash */
		public readonly ?string $integrity = null,
	) {
	}


	public function __toString(): string
	{
		return $this->url;
	}
}
