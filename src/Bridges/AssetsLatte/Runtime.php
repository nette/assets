<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Bridges\AssetsLatte;

use Nette;
use Nette\Assets\Asset;
use Nette\Assets\EntryAsset;
use Nette\Assets\HtmlRenderable;
use Nette\Assets\Registry;
use Nette\Assets\ScriptAsset;
use Nette\Assets\StyleAsset;


/**
 * Runtime helpers for Latte.
 * @internal
 */
class Runtime
{
	public function __construct(
		private readonly Registry $registry,
	) {
	}


	public function resolve(string|array|Asset $asset, array $options, bool $try): ?Asset
	{
		return match (true) {
			$asset instanceof Asset => $asset,
			$try => $this->registry->tryGetAsset($asset, $options),
			default => $this->registry->getAsset($asset, $options),
		};
	}


	public function renderAsset(Asset $asset): string
	{
		if (!$asset instanceof HtmlRenderable) {
			throw new Nette\InvalidArgumentException('This asset type cannot be rendered as HTML.');
		}

		$res = (string) $asset->getHtmlElement();

		if ($asset instanceof EntryAsset) {
			foreach ($asset->dependencies as $dep) {
				$res .= match (true) {
					$dep instanceof ScriptAsset => $dep->getHtmlPreloadElement(),
					$dep instanceof StyleAsset => $dep->getHtmlElement(),
					default => '',
				};
			}
		}

		return $res;
	}


	public function renderAssetPreload(Asset $asset): string
	{
		if (!$asset instanceof HtmlRenderable) {
			throw new Nette\InvalidArgumentException('This asset type cannot be preloaded.');
		}

		return (string) $asset->getHtmlPreloadElement();
	}


	public function renderAttributes(Asset $asset, string $tagName, array $usedAttributes): string
	{
		if (!$asset instanceof HtmlRenderable) {
			throw new Nette\InvalidArgumentException('This asset type cannot be rendered with attributes.');
		}

		$el = $asset->getHtmlElement();
		if ($el->getName() !== $tagName) {
			if ($tagName === 'link') {
				$el = $asset->getHtmlPreloadElement();
			} elseif ($tagName === 'a') {
				$el = Nette\Utils\Html::el('a', ['href' => $el->src]);
			} else {
				throw new Nette\InvalidArgumentException("Tag <$tagName> is not allowed for this asset. Use <{$el->getName()}> instead.");
			}
		}

		if (isset($usedAttributes['width']) || isset($usedAttributes['height'])) {
			unset($el->attrs['width'], $el->attrs['height']);
		}

		$el->attrs = array_diff_key($el->attrs, $usedAttributes);
		return $el->attributes();
	}
}
