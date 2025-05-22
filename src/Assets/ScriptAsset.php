<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Script asset.
 */
class ScriptAsset implements Asset
{
	public function __construct(
		public readonly string $url,
		public readonly ?string $mimeType = null,
		public readonly ?string $file = null,
		public readonly ?string $type = null,
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
