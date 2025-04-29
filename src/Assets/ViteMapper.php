<?php

declare(strict_types=1);

namespace Nette\Assets;

use Nette\Utils\FileSystem;
use Nette\Utils\Json;


/**
 * Maps asset references to Vite-generated files using a Vite manifest.json.
 * Supports both development mode (Vite dev server) and production mode.
 */
class ViteMapper implements Mapper
{
	private array $chunks;
	private array $dependencies = [];


	public function __construct(
		private readonly string $baseUrl,
		private readonly string $basePath,
		private readonly ?string $manifestPath = null,
		private readonly ?string $devServerUrl = null,
	) {
	}


	/**
	 * Retrieves an Asset for a given Vite entry point.
	 * In dev mode, returns assets pointing to Vite dev server.
	 * In production, uses the manifest to find the file and its dependencies.
	 * @throws AssetNotFoundException when the asset cannot be found in the manifest
	 */
	public function getAsset(string $reference, array $options = []): Asset
	{
		Helpers::checkOptions($options);

		if (!empty($this->devServerUrl)) {
			$url = rtrim($this->devServerUrl, '/') . '/' . $reference;
			return new EntryAsset(
				url: $url,
				mimeType: Helpers::guessMimeTypeFromExtension($url),
				dependencies: [],
				sourcePath: null,
				integrity: null,
			);
		}

		$this->chunks ??= $this->readChunks();
		$entry = $this->chunks[$id = $reference]
			?? $this->chunks[$id = $this->findByName($reference)]
			?? throw new AssetNotFoundException("Entry '$reference' not found in Vite manifest");

		if (str_starts_with($reference, '_') && !isset($entry['isEntry'])) {
			throw new AssetNotFoundException("Cannot directly access internal chunk '$reference'");
		}

		$dependencies = $this->collectDependencies($id);
		unset($dependencies[$entry['file']]);

		return $dependencies
			? new EntryAsset(
				url: $this->baseUrl . '/' . $entry['file'],
				mimeType: Helpers::guessMimeTypeFromExtension($entry['file']),
				sourcePath: $this->basePath . '/' . $entry['file'],
				dependencies: array_values($dependencies),
			)
			: $this->createAsset($entry['file']);
	}


	/**
	 * Recursively collects all imports (including nested) from a chunk
	 */
	private function collectDependencies(string $chunkId): array
	{
		$deps = &$this->dependencies[$chunkId];
		if ($deps === null) {
			$deps = [];
			$entry = $this->chunks[$chunkId] ?? [];
			foreach ($entry['css'] ?? [] as $file) {
				$deps[$file] = $this->createAsset($file);
			}
			foreach ($entry['imports'] ?? [] as $id) {
				$file = $this->chunks[$id]['file'];
				$deps[$file] = $this->createAsset($file);
				$deps += $this->collectDependencies($id);
			}
		}
		return $deps;
	}


	private function findByName(string $name): ?string
	{
		foreach ($this->chunks as $id => $entry) {
			if (isset($entry['name'], $entry['isEntry']) && $entry['name'] === $name) {
				return $id;
			}
		}
		return null;
	}


	private function readChunks(): array
	{
		$path = $this->manifestPath ?? $this->basePath . '/.vite/manifest.json';
		try {
			$res = Json::decode(FileSystem::read($path), forceArrays: true);
		} catch (\Throwable $e) {
			throw new \RuntimeException('Failed to parse Vite manifest: ' . $e->getMessage(), 0, $e);
		}
		if (!is_array($res)) {
			throw new \RuntimeException('Invalid Vite manifest format in ' . $path);
		}
		return $res;
	}


	private function createAsset(string $file): Asset
	{
		return Helpers::createAssetFromUrl($this->baseUrl . '/' . $file, $this->basePath . '/' . $file);
	}
}
