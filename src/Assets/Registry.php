<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Central registry of asset providers and cache for resolved assets.
 * Allows getting assets by their qualified path (provider:path).
 */
final class Registry
{
	public const DefaultScope = '';
	private const ProviderSeparator = ':';
	private const MaxCacheSize = 10;

	/** @var array<string, Provider> */
	private array $providers = [];

	/** @var array<string, Asset> */
	private array $cache = [];


	/**
	 * Adds new asset provider to the registry.
	 * @throws \InvalidArgumentException if provider with same ID already exists
	 */
	public function addProvider(string $id, Provider $provider): void
	{
		if (isset($this->providers[$id])) {
			throw new \InvalidArgumentException("Asset provider '$id' is already registered");
		}
		$this->providers[$id] = $provider;
	}


	/**
	 * Returns asset provider by its ID.
	 * @throws \InvalidArgumentException if provider doesn't exist
	 */
	public function getProvider(string $id): Provider
	{
		return $this->providers[$id] ?? throw new \InvalidArgumentException("Unknown asset provider '$id'.");
	}


	/**
	 * Returns asset instance for given provider-qualified path.
	 */
	public function getAsset(string $qualifiedPath, array $options = []): Asset
	{
		$cacheKey = $qualifiedPath . ($options ? '?' . http_build_query($options) : '');
		if (isset($this->cache[$cacheKey])) {
			return $this->cache[$cacheKey];
		}

		[$provider, $path] = $this->parsePath($qualifiedPath);
		$asset = $this->getProvider($provider)->getAsset($path, $options);

		if (count($this->cache) >= self::MaxCacheSize) {
			array_shift($this->cache);
		}

		return $this->cache[$cacheKey] = $asset;
	}


	/**
	 * Parses provider-qualified path into [providerId, path] parts.
	 * @return array{string, string}
	 */
	private function parsePath(string $qualifiedPath): array
	{
		$parts = explode(self::ProviderSeparator, $qualifiedPath, 2);
		return count($parts) === 1 ? [self::DefaultScope, $parts[0]] : $parts;
	}
}
