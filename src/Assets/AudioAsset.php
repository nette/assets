<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Audio asset.
 */
class AudioAsset implements Asset
{
	use LazyLoad;

	/** Duration in seconds */
	public readonly ?float $duration;


	public function __construct(
		public readonly string $url,
		public readonly ?string $file = null,
		?float $duration = null,
	) {
		$this->lazyLoad(compact('duration'), fn() => $this->duration = $this->file
			? Helpers::guessMP3Duration($this->file)
			: null);
	}


	public function __toString(): string
	{
		return $this->url;
	}
}
