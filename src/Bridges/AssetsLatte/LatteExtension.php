<?php

declare(strict_types=1);

namespace Nette\Bridges\AssetsLatte;

use Latte\Extension;
use Nette\Assets\Asset;
use Nette\Assets\Registry;


/**
 * Latte extension that provides asset-related functions:
 * - asset(): returns asset URL or throws AssetNotFoundException if asset not found
 * - tryAsset(): returns asset URL or null if asset not found
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
			'asset' => fn(string|array $reference, ...$options): Asset => $this->registry->getAsset($reference, $options),
			'tryAsset' => fn(string|array $reference, ...$options): ?Asset => $this->registry->tryGetAsset($reference, $options),
		];
	}
}
