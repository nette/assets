<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Central registry of asset mappers and cache for resolved assets.
 * Allows getting assets by their qualified path (mapper:path).
 */
final class Registry
{
	public const DefaultScope = '';
	private const MapperSeparator = ':';
	private const MaxCacheSize = 10;

	/** @var array<string, Mapper> */
	private array $mappers = [];

	/** @var array<string, Asset> */
	private array $cache = [];


	/**
	 * Adds new asset mapper to the registry.
	 * @throws \InvalidArgumentException
	 */
	public function addMapper(string $id, Mapper $mapper): void
	{
		if (isset($this->mappers[$id])) {
			throw new \InvalidArgumentException("Asset mapper '$id' is already registered");
		}
		$this->mappers[$id] = $mapper;
	}


	/**
	 * Returns asset mapper by its ID.
	 * @throws \InvalidArgumentException
	 */
	public function getMapper(string $id = ''): Mapper
	{
		return $this->mappers[$id] ?? throw new \InvalidArgumentException("Unknown asset mapper '$id'.");
	}


	/**
	 * Returns asset instance for given mapper-qualified reference.
	 */
	public function getAsset(string $qualifiedRef, array $options = []): ?Asset
	{
		$cacheKey = $qualifiedRef . ($options ? '?' . http_build_query($options) : '');
		if (array_key_exists($cacheKey, $this->cache)) {
			return $this->cache[$cacheKey];
		}

		[$mapper, $path] = $this->parsePath($qualifiedRef);
		$asset = $this->getMapper($mapper)->getAsset($path, $options);

		if (count($this->cache) >= self::MaxCacheSize) {
			array_shift($this->cache);
		}

		return $this->cache[$cacheKey] = $asset;
	}


	/**
	 * Parses mapper-qualified path into [mapperId, path] parts.
	 * @return array{string, string}
	 */
	private function parsePath(string $qualifiedPath): array
	{
		$parts = explode(self::MapperSeparator, $qualifiedPath, 2);
		return count($parts) === 1 ? [self::DefaultScope, $parts[0]] : $parts;
	}
}
