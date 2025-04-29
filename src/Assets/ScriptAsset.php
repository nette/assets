<?php

declare(strict_types=1);

namespace Nette\Assets;

use Nette\Utils\Html;


/**
 * Script asset.
 */
class ScriptAsset implements Asset, HtmlRenderable
{
	public function __construct(
		public readonly string $url,
		public readonly ?string $mimeType = null,
		public readonly ?string $file = null,
		public readonly ?string $type = null,
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
		return Html::el('script', array_filter([
			'src' => $this->url,
			'type' => $this->type,
			'integrity' => $this->integrity,
			'crossorigin' => $this->integrity ? 'anonymous' : null,
		], fn($value) => $value !== null));
	}


	public function getHtmlPreloadElement(): Html
	{
		return Html::el('link', $this->type === 'module'
			? ['rel' => 'modulepreload', 'href' => $this->url]
			: ['rel' => 'preload', 'href' => $this->url, 'as' => 'script']);
	}
}
