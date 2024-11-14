<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Asset mapper that serves files from a local filesystem directory.
 * Supports file versioning based on modification time and automatic extension detection.
 */
class FilesystemMapper implements Mapper
{
	protected readonly string $baseUrl;
	protected readonly string $basePath;
	protected readonly array $extensions;


	public function __construct(string $baseUrl, string $basePath, array $extensions = [])
	{
		$this->baseUrl = rtrim($baseUrl, '/');
		$this->basePath = rtrim($basePath, '\/');
		$this->extensions = $extensions;
	}


	/**
	 * Returns asset instance for given reference.
	 */
	public function getAsset(string $reference, array $options = []): FileAsset
	{
		Helpers::checkOptions($options);
		$path = $this->resolvePath($reference);
		$ext = $this->extensions && !is_file($path)
			? $this->findExtension($path)
			: '';
		return new FileAsset($this->buildUrl($reference . $ext, $options), $path . $ext);
	}


	/**
	 * Returns public URL for the given relative path by combining it with the base URL.
	 */
	public function resolveUrl(string $reference): string
	{
		return $this->baseUrl . '/' . $reference;
	}


	/**
	 * Returns filesystem path for the given relative path by combining it with the base path.
	 */
	public function resolvePath(string $reference): string
	{
		return $this->basePath . '/' . $reference;
	}


	/**
	 * Builds public URL for the asset including optional version parameter.
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
	 * Returns version string for the asset based on file modification time.
	 */
	protected function getVersion(string $reference): ?string
	{
		$path = $this->resolvePath($reference);
		return is_file($path) ? (string) filemtime($path) : null;
	}


	/**
	 * Applies version to asset URL as query parameter.
	 */
	protected function applyVersion(string $url, string $version): string
	{
		return $url . '?v=' . $version;
	}


	/**
	 * Finds extension for the asset file based on the list of possible extensions.
	 */
	private function findExtension(string $path): string
	{
		foreach ($this->extensions as $ext) {
			if ($ext === '') {
				$default = '';
			} else {
				$ext = '.' . $ext;
				$default ??= $ext;
			}
			if (is_file($path . $ext)) {
				return $ext;
			}
		}

		return $default;
	}
}
