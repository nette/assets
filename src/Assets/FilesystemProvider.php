<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Asset provider that serves files from a local filesystem directory.
 * Supports file versioning based on modification time and automatic extension detection.
 */
class FilesystemProvider implements Provider
{
	protected readonly string $baseUrl;
	protected readonly string $basePath;
	protected readonly array $extensions;


	public function __construct(string $baseUrl, string $basePath, array $extensions = [])
	{
		$this->baseUrl = rtrim($baseUrl, '/');
		$this->basePath = rtrim($basePath, '\\/');
		$this->extensions = $extensions;
	}


	/**
	 * Returns asset instance for given path.
	 */
	public function getAsset(string $path, array $options = []): FileAsset
	{
		Helpers::checkOptions($options);
		$sourcePath = $this->getSourcePath($path);
		$ext = $this->extensions && !file_exists($sourcePath)
			? $this->findExtension($sourcePath)
			: '';
		return new FileAsset($this->buildUrl($path . $ext, $options), $sourcePath . $ext);
	}


	/**
	 * Builds public URL for the asset including optional version parameter.
	 */
	protected function buildUrl(string $path, array $options): string
	{
		if ($version = $this->getVersion($path)) {
			$path = $this->applyVersion($path, $version);
		}
		return $this->baseUrl . '/' . $path;
	}


	/**
	 * Returns filesystem path for the asset.
	 */
	protected function getSourcePath(string $path): string
	{
		return $this->basePath . '/' . $path;
	}


	/**
	 * Returns version string for the asset based on file modification time.
	 */
	protected function getVersion(string $path): ?string
	{
		$sourcePath = $this->getSourcePath($path);
		return is_file($sourcePath) ? (string) filemtime($sourcePath) : null;
	}


	/**
	 * Applies version to asset URL as query parameter.
	 */
	protected function applyVersion(string $path, string $version): string
	{
		return $path . '?v=' . $version;
	}


	/**
	 * Finds extension for the asset file based on the list of possible extensions.
	 */
	private function findExtension(string $sourcePath): string
	{
		foreach ($this->extensions as $ext) {
			if ($ext === '') {
				$default = '';
			} else {
				$default ??= ($ext = '.' . $ext);
			}
			if (is_file($sourcePath . $ext)) {
				return $ext;
			}
		}

		return $default;
	}
}
