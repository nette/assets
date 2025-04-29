<?php

declare(strict_types=1);

namespace Nette\Assets;

use Nette\Utils\Html;


/**
 * Font asset.
 */
class FontAsset implements Asset, HtmlRenderable
{
	public function __construct(
		public readonly string $url,
		public readonly ?string $mimeType = null,
		public readonly ?string $file = null,
		/** SRI integrity hash */
		public readonly ?string $integrity = null,
	) {
	}


	public function __toString(): string
	{
		return $this->url;
	}


	public function getHtmlElement(): Html
	{
		return Html::el('link', array_filter([
			'rel' => 'preload',
			'href' => $this->url,
			'as' => 'font',
			'type' => $this->mimeType,
			'crossorigin' => 'anonymous',
			'integrity' => $this->integrity,
		], fn($value) => $value !== null));
	}


	public function getHtmlPreloadElement(): Html
	{
		return $this->getHtmlElement();
	}
}
