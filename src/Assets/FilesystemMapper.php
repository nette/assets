<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Maps asset references to files within a specified local directory.
 * Supports versioning based on file modification time and optional extension auto-detection.
 */
class FilesystemMapper implements Mapper
{
	public function __construct(
		protected readonly string $baseUrl,
		protected readonly string $basePath,
		protected readonly array $extensions = [],
	) {
	}


	/**
	 * Resolves a relative reference to a asset within the configured base path.
	 * Attempts to find a matching extension if configured.
	 * @throws \InvalidArgumentException For unsupported options.
	 * @throws AssetNotFoundException when the file doesn't exist
	 */
	public function getAsset(string $reference, array $options = []): Asset
	{
		Helpers::checkOptions($options);
		$path = $this->resolvePath($reference);
		$path .= $ext = $this->findExtension($path);

		if (!is_file($path)) {
			throw new AssetNotFoundException("Asset file '$reference' not found at path: '$path'");
		}

		return Helpers::createAssetFromUrl($this->buildUrl($reference . $ext, $options), $path);
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


	/**
	 * Builds the final public URL, potentially including a version query parameter.
	 */
	protected function buildUrl(string $reference, array $options): string
	{
		$url = $this->resolveUrl($reference);
		if ($version = $this->getVersion($reference)) {
			$url = $this->applyVersion($url, $version);
		}
		return $url;
	}


	/**
	 * Determines the version string for an asset, typically based on file modification time.
	 */
	protected function getVersion(string $reference): ?string
	{
		$path = $this->resolvePath($reference);
		return is_file($path) && is_int($tmp = filemtime($path)) ? (string) $tmp : null;
	}


	/**
	 * Appends the version string to the URL as a query parameter '?v=...'.
	 */
	protected function applyVersion(string $url, string $version): string
	{
		return $url . '?v=' . $version;
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
