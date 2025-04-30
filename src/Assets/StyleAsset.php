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
		public readonly ?string $sourcePath = null,
	) {
	}


	public function __toString(): string
	{
		return $this->url;
	}
}
