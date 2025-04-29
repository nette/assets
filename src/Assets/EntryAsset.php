<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Entry point asset implementation that can represent both script and style entry points.
 */
class EntryAsset extends ScriptAsset
{
	public function __construct(
		public readonly string $url,
		public readonly ?string $mimeType = null,
		public array $dependencies = [],
		public readonly ?string $sourcePath = null,
		public readonly ?string $integrity = null,
	) {
	}
}
