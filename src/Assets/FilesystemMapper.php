<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Maps asset references to files within a specified local directory.
 * Supports configurable versioning and optional extension auto-detection.
 */
class FilesystemMapper implements Mapper
{
	private const OptionVersion = 'version';


	public function __construct(
		protected readonly string $baseUrl,
		protected readonly string $basePath,
		protected readonly array $extensions = [],
		protected readonly bool $versioning = true,
	) {
	}


	/**
	 * Resolves a relative reference to an asset within the configured base path.
	 * Attempts to find a matching extension if configured and applies versioning if enabled.
	 * Available options: 'version' => bool: Whether to apply versioning (defaults to true)
	 */
	public function getAsset(string $reference, array $options = []): Asset
	{
		Helpers::checkOptions($options, [self::OptionVersion]);
		$path = $this->resolvePath($reference);
		$path .= $ext = $this->findExtension($path);

		if (!is_file($path)) {
			throw new AssetNotFoundException("Asset file '$reference' not found at path: '$path'");
		}

		$url = $this->resolveUrl($reference . $ext);
		if ($options[self::OptionVersion] ?? $this->versioning) {
			$url = $this->applyVersion($url, $path);
		}
		return Helpers::createAssetFromUrl($url, $path);
	}


	/**
	 * Constructs the full public URL by prepending the base URL to the reference.
	 */
	public function resolveUrl(string $reference): string
	{
		return $this->baseUrl . '/' . $reference;
	}


	/**
	 * Constructs the full filesystem path by prepending the base path to the reference.
	 */
	public function resolvePath(string $reference): string
	{
		return $this->basePath . '/' . $reference;
	}


	protected function applyVersion(string $url, string $path): string
	{
		if (is_int($version = filemtime($path))) {
			$url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . $version;
		}
		return $url;
	}


	/**
	 * Searches for an existing file by appending configured extensions to the base path.
	 */
	private function findExtension(string $basePath): string
	{
		$defaultExt = null;
		foreach ($this->extensions as $ext) {
			if ($ext === '') {
				$defaultExt = '';
			} else {
				$ext = '.' . $ext;
				$defaultExt ??= $ext;
			}
			if (is_file($basePath . $ext)) {
				return $ext;
			}
		}

		return $defaultExt ?? '';
	}
}
