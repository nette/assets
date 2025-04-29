<?php

declare(strict_types=1);

namespace Nette\Bridges\Assets;

use Latte\Extension;
use Nette\Assets;


/**
 * Latte extension that provides asset-related functions:
 * - asset(): returns asset URL or throws AssetNotFoundException if asset not found
 * - tryAsset(): returns asset URL or null if asset not found
 * - renderAsset(): renders entry asset with all required tags
 */
final class LatteExtension extends Extension
{
	public function __construct(
		private readonly Assets\Registry $registry,
	) {
	}


	public function getFunctions(): array
	{
		return [
			'asset' => $this->registry->getAsset(...),
			'tryAsset' => $this->registry->tryGetAsset(...),
			'renderAsset' => $this->renderAsset(...),
		];
	}


	/**
	 * Renders entry asset with all required tags (script, stylesheet).
	 * For JS entries, automatically includes all imported chunks.
	 * @throws Assets\AssetNotFoundException if the asset is not found
	 */
	public function renderAsset(string|array|Assets\Asset $asset, array $options = []): string
	{
		$asset = $asset instanceof Assets\Asset
			? $asset
			: $this->registry->getAsset($asset, $options);


		if ($asset instanceof Assets\EntryAsset) {
			$output = '';

			// TODO: In development mode, add the Vite client script
			if (property_exists($asset, 'isDev') && $asset->isDev) {
				$output .= '<script src="' . htmlspecialchars(dirname($asset->url)) . '/@vite/client" type="module"></script>';
			}

			$output .= '<script src="' . htmlspecialchars($asset->url) . '" type="module"></script>'; // + nonce, využít kod nize?

			foreach ($asset->dependencies as $dependency) {
				$output .= match (true) {
					$dependency instanceof Assets\ScriptAsset => '<link rel="modulepreload" src="' . htmlspecialchars($dependency->url) . '"></script>',
					$dependency instanceof Assets\StyleAsset => $this->renderAsset($dependency),
					default => '',
				};
			}

			return $output;

		} elseif ($asset instanceof Assets\ScriptAsset) {
			return '<script '
				. 'src="' . htmlspecialchars($asset->url) . '"'
				// 'echo $this->global->uiNonce ? " nonce=\"{$this->global->uiNonce}\"" : "";'
				. ($asset->module ? ' type="module"' : '')
				. ($asset->integrity ? ' integrity="' . htmlspecialchars($asset->integrity) . '" crossorigin="anonymous"' : '')
				. '></script>';

		} elseif ($asset instanceof Assets\StyleAsset) {
			return '<link rel="stylesheet" href="' . htmlspecialchars($asset->url) . '"'
				. ($asset->media ? ' media="' . htmlspecialchars($asset->media) . '"' : '')
				. ($asset->integrity ? ' integrity="' . htmlspecialchars($asset->integrity) . '" crossorigin="anonymous"' : '')
				. '>';

		} elseif ($asset instanceof Assets\ImageAsset) {
			return '<img src="' . htmlspecialchars($asset->url) . '"'
				. ($asset->width ? ' width="' . $asset->width . '"' : '')
				. ($asset->height ? ' height="' . $asset->height . '"' : '')
				. ($asset->alternative ? ' alt="' . htmlspecialchars($asset->alternative) . '"' : '')
				. ($asset->lazyLoad ? ' loading="lazy"' : '')
				. '>';

		} elseif ($asset instanceof Assets\AudioAsset) {
			return '<audio src="' . htmlspecialchars($asset->url) . '"'
				. ($asset->duration ? ' duration="' . $asset->duration . '"' : '')
				. '></audio>';

		} elseif ($asset instanceof Assets\VideoAsset) {
			return '<video src="' . htmlspecialchars($asset->url) . '"'
				. ($asset->width ? ' width="' . $asset->width . '"' : '')
				. ($asset->height ? ' height="' . $asset->height . '"' : '')
				. '></video>';

		} else {
			throw new \InvalidArgumentException('Unsupported asset type: ' . $asset::class);
		}
	}
}
