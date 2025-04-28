<?php

declare(strict_types=1);

namespace Nette\Bridges\AssetsLatte;

use Latte\Extension;
use Nette\Assets\Registry;


/**
 * Latte extension that provides asset-related functions:
 * - asset(): returns asset URL
 */
final class LatteExtension extends Extension
{
	public function __construct(
		private readonly Registry $registry,
	) {
	}


	public function getFunctions(): array
	{
		return [
			'asset' => $this->registry->getAsset(...),
		];
	}
}
