<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Style asset.
 */
class StyleAsset implements Asset
{
	public function __construct(
		public readonly string $url,
		public readonly ?string $mimeType = null,
		public readonly ?string $file = null,
		/** Media query for the stylesheet */
		public readonly ?string $media = null,
		/** SRI integrity hash */
		public readonly ?string $integrity = null,
		public readonly string|bool|null $crossorigin = null,
	) {
	}


	public function __toString(): string
	{
		return $this->url;
	}
}
