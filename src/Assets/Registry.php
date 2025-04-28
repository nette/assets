<?php

declare(strict_types=1);

namespace Nette\Assets;


/**
 * Manages a collection of named asset Mappers and provides a central point
 * for retrieving Assets using qualified references (mapper:reference).
 * Includes a simple cache for resolved assets.
 */
class Registry
{
	public const DefaultScope = '';
	private const MapperSeparator = ':';
	private const MaxCacheSize = 100;

	/** @var array<string, Mapper> */
	private array $mappers = [];

	/** @var array<string, Asset> */
	private array $cache = [];


	/**
	 * Registers a new asset mapper under a specific identifier.
	 * @throws \InvalidArgumentException If the identifier is already in use.
	 */
	public function addMapper(string $id, Mapper $mapper): void
	{
		if (isset($this->mappers[$id])) {
			throw new \InvalidArgumentException("Asset mapper '$id' is already registered");
		}
		$this->mappers[$id] = $mapper;
	}


	/**
	 * Retrieves a registered asset mapper by its identifier.
	 * @throws \InvalidArgumentException If the requested mapper identifier is unknown.
	 */
	public function getMapper(string $id = self::DefaultScope): Mapper
	{
		return $this->mappers[$id] ?? throw new \InvalidArgumentException("Unknown asset mapper '$id'.");
	}


	/**
	 * Retrieves an Asset instance using a qualified reference. Accepts either 'mapper:reference' or ['mapper', 'reference'].
	 * Options passed directly to the underlying Mapper::getAsset() method.
	 * @throws AssetNotFoundException when the asset cannot be found
	 */
	public function getAsset(string|array $qualifiedRef, array $options = []): Asset
	{
		[$mapper, $reference] = is_string($qualifiedRef)
			? $this->parseReference($qualifiedRef)
			: $qualifiedRef;

		$reference = (string) $reference;
		$cacheKey = $mapper . ':' . $reference . ($options ? '?' . http_build_query($options) : '');
		if (array_key_exists($cacheKey, $this->cache)) {
			return $this->cache[$cacheKey];
		}

		try {
			$asset = $this->getMapper($mapper)->getAsset($reference, $options);

			if (count($this->cache) >= self::MaxCacheSize) {
				array_shift($this->cache); // remove the oldest entry
			}

			return $this->cache[$cacheKey] = $asset;
		} catch (AssetNotFoundException $e) {
			throw $e->qualifyReference($mapper, $reference);
		}
	}


	/**
	 * Splits a potentially qualified reference 'mapper:reference' into a [mapper, reference] array.
	 * @return array{string, string}
	 */
	private function parseReference(string $qualifiedRef): array
	{
		$parts = explode(self::MapperSeparator, $qualifiedRef, 2);
		return count($parts) === 1
			? [self::DefaultScope, $parts[0]]
			: [$parts[0], $parts[1]];
	}
}
