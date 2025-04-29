<?php

declare(strict_types=1);

use Nette\Assets\EntryAsset;
use Nette\Assets\Registry;
use Nette\Assets\ViteMapper;
use Nette\Bridges\Assets\DIExtension;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nette\Http\UrlImmutable;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$baseUrl = new UrlImmutable('https://example.com/');
$basePath = __DIR__ . '/../Assets/fixtures';

// Prepare container for testing
$loader = new ContainerLoader(getTempDir(), true);
$key = __FILE__;

$class = $loader->load(function (Compiler $compiler) use ($baseUrl, $basePath): void {
	$compiler->addExtension('assets', new DIExtension());
	$compiler->addConfig([
		'parameters' => [
			'wwwDir' => $basePath,
			'debugMode' => false,
		],
		'assets' => [
			'baseUrl' => 'https://example.com',
			'basePath' => $basePath,
			'mapping' => [
				'vite-prod' => [
					'type' => 'vite',
					'url' => '/dist',
					'path' => $basePath,
					'manifest' => 'manifest.json',
				],
				'vite-dev' => [
					'type' => 'vite',
					'url' => '/dist',
					'path' => $basePath,
					'manifest' => 'manifest.json',
					'debug' => true,
				],
				'vite-auto' => [
					'type' => 'vite',
					'url' => '/dist',
					'path' => $basePath,
					'manifest' => 'manifest.json',
					'debug' => '%debugMode%',
				],
			],
		],
	]);
}, $key);

$container = new $class();
Assert::type(Container::class, $container);

// Helper method for testing if an asset is in development mode
function isDev(EntryAsset $asset): bool
{
	// Development mode assets have no dependencies
	return count($asset->dependencies) === 0;
}


// Test Vite production mapper configuration
test('ViteMapper production configuration', function () use ($container): void {
	$registry = $container->getByType(Registry::class);
	Assert::type(Registry::class, $registry);

	// Test production mapper
	$viteMapper = $registry->getMapper('vite-prod');
	Assert::type(ViteMapper::class, $viteMapper);

	// Get an asset through the registry
	$asset = $registry->getAsset('vite-prod:src/main.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('https://example.com/dist/assets/main-1a2b3c4d.js', $asset->url);

	// Test imports
	Assert::count(2, $asset->dependencies);

	// Test that dev mode is false in production
	Assert::false(isDev($asset));
});

// Test Vite dev mapper configuration with explicit debug flag
test('ViteMapper dev configuration with explicit debug flag', function () use ($container): void {
	$registry = $container->getByType(Registry::class);
	Assert::type(Registry::class, $registry);

	// Test dev mapper
	$viteMapper = $registry->getMapper('vite-dev');
	Assert::type(ViteMapper::class, $viteMapper);

	// Get an asset through the registry
	$asset = $registry->getAsset('vite-dev:src/main.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('http://localhost:5173/src/main.js', $asset->url);

	// Test that imports are empty in dev mode
	Assert::count(0, $asset->dependencies);

	// Test that dev mode is true
	Assert::true(isDev($asset));
});

// Test Vite mapper with debug flag from parameter
test('ViteMapper with debug flag from parameter', function () use ($container): void {
	$registry = $container->getByType(Registry::class);
	Assert::type(Registry::class, $registry);

	// Test auto mapper (should be in production mode because debugMode = false)
	$viteMapper = $registry->getMapper('vite-auto');
	Assert::type(ViteMapper::class, $viteMapper);

	// Get an asset through the registry
	$asset = $registry->getAsset('vite-auto:src/main.js');
	Assert::type(EntryAsset::class, $asset);

	// Should be in production mode
	Assert::false(isDev($asset));
	Assert::same('https://example.com/dist/assets/main-1a2b3c4d.js', $asset->url);
});
