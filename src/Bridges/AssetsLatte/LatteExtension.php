<?php

declare(strict_types=1);

namespace Nette\Bridges\AssetsLatte;

use Latte\Extension;
use Nette\Assets\Asset;
use Nette\Assets\Registry;


/**
 * Latte extension that provides asset-related functions and tags:
 * - asset(): returns asset URL or throws AssetNotFoundException if asset not found
 * - tryAsset(): returns asset URL or null if asset not found
 * - {asset ...} & {asset? ...} renders HTML code
 * - {preload ...} renders HTML code for preloading
 * - n:asset renders HTML attributes
 */
final class LatteExtension extends Extension
{
	public function __construct(
		private readonly Registry $registry,
	) {
	}


	public function getTags(): array
	{
		return [
			'asset' => Nodes\AssetNode::create(...),
			'asset?' => Nodes\AssetNode::create(...),
			'preload' => Nodes\AssetNode::create(...),
			'n:asset' => Nodes\NAssetNode::create(...),
			'n:asset?' => Nodes\NAssetNode::create(...),
		];
	}


	public function getFunctions(): array
	{
		return [
			'asset' => fn(string|array $reference, ...$options): Asset => $this->registry->getAsset($reference, $options),
			'tryAsset' => fn(string|array $reference, ...$options): ?Asset => $this->registry->tryGetAsset($reference, $options),
		];
	}


	public function getProviders(): array
	{
		return [
			'assets' => new Runtime($this->registry),
		];
	}
}
