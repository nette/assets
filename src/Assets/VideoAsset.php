<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Video asset.
 */
class VideoAsset implements Asset
{
	public function __construct(
		public readonly string $url,
		public readonly ?string $file = null,
		public readonly ?int $width = null,
		public readonly ?int $height = null,
		/** Duration in seconds */
		public readonly ?float $duration = null,
	) {
	}


	public function __toString(): string
	{
		return $this->url;
	}
}
