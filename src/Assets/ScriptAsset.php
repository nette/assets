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
		public readonly ?string $sourcePath = null,
		public readonly bool $module = false,
		/** SRI integrity hash */
		public readonly ?string $integrity = null,
	) {
	}


	public function __toString(): string
	{
		return $this->url;
	}
}
