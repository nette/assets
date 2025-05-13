<?php

declare(strict_types=1);

use Nette\Assets\FilesystemMapper;
use Nette\Assets\ScriptAsset;
use Nette\Assets\StyleAsset;
use Nette\Assets\ViteMapper;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('Production mode - JS entry point with imports and CSS', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest.json');
	$asset = $mapper->getAsset('src/main.js');

	Assert::type(ScriptAsset::class, $asset);
	Assert::same('https://example.com/assets/main-1a2b3c4d.js', $asset->url);

	// Check imports and preloads
	assertAssets([
		new StyleAsset('https://example.com/assets/main-a1b2c3d4.css', mimeType: 'text/css', file: __DIR__ . '/fixtures/assets/main-a1b2c3d4.css', crossorigin: true),
	], $asset->imports);
	assertAssets([
		new ScriptAsset('https://example.com/assets/shared-5e6f7g8h.js', mimeType: 'application/javascript', file: __DIR__ . '/fixtures/assets/shared-5e6f7g8h.js', type: 'module', crossorigin: true),
	], $asset->preloads);
});


test('Production mode - CSS entry point', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest.json');
	$asset = $mapper->getAsset('src/styles.css');

	Assert::same('https://example.com/assets/styles-9i0j1k2l.css', $asset->url);
});


test('Fallback to filesystem when asset not found in manifest', function (): void {
	$filesystemMapper = new FilesystemMapper('https://example.com', __DIR__ . '/fixtures');
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest.json', publicMapper: $filesystemMapper);

	// image.gif exists on filesystem but not in the manifest
	$asset = $mapper->getAsset('image.gif');

	Assert::match('https://example.com/image.gif?v=%a%', $asset->url);
	Assert::same('image/gif', $asset->mimeType);
	Assert::same(__DIR__ . '/fixtures/image.gif', $asset->file);
});


test('Development mode', function (): void {
	$mapper = new ViteMapper(
		'https://example.com',
		__DIR__ . '/fixtures',
		__DIR__ . '/fixtures/manifest.json',
		'http://localhost:5173',
	);
	$asset = $mapper->getAsset('src/main.js');

	Assert::type(ScriptAsset::class, $asset);
	Assert::same('http://localhost:5173/src/main.js', $asset->url);

	// In dev mode, check imports
	Assert::count(0, $asset->preloads);
	assertAssets([
		new ScriptAsset('http://localhost:5173/@vite/client', type: 'module'),
	], $asset->imports);
});


test('Asset not found in manifest or filesystem', function (): void {
	$manifestPath = __DIR__ . '/fixtures/manifest.json';

	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', $manifestPath);

	Assert::exception(
		function () use ($mapper): void {
			$mapper->getAsset('non-existent.js');
		},
		Nette\Assets\AssetNotFoundException::class,
		"File 'non-existent.js' not found in Vite manifest",
	);
});


test('Default manifest path', function (): void {
	$basePath = __DIR__ . '/fixtures';

	// Create a backup of the manifest in the default location
	@mkdir(dirname($basePath . '/.vite/manifest.json'), 0o777, true);
	@copy($basePath . '/manifest.json', $basePath . '/.vite/manifest.json');

	try {
		$mapper = new ViteMapper('https://example.com', $basePath); // No manifest path provided
		$asset = $mapper->getAsset('src/main.js');

		Assert::type(ScriptAsset::class, $asset);
		Assert::same('https://example.com/assets/main-1a2b3c4d.js', $asset->url);
	} finally {
		// Clean up
		@unlink($basePath . '/.vite/manifest.json');
		@rmdir($basePath . '/.vite');
	}
});
