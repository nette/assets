<?php

declare(strict_types=1);

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

	// Check dependencies
	Assert::count(2, $asset->dependencies);

	// Verify types of dependencies
	$scriptDeps = array_filter($asset->dependencies, fn($dep) => $dep instanceof ScriptAsset);
	$styleDeps = array_filter($asset->dependencies, fn($dep) => $dep instanceof StyleAsset);

	Assert::count(1, $scriptDeps);
	Assert::count(1, $styleDeps);

	// Verify script dependency
	$scriptDep = reset($scriptDeps);
	Assert::same('https://example.com/assets/shared-5e6f7g8h.js', $scriptDep->url);
	Assert::same('application/javascript', $scriptDep->mimeType);

	// Verify style dependency
	$styleDep = reset($styleDeps);
	Assert::same('https://example.com/assets/main-a1b2c3d4.css', $styleDep->url);
	Assert::same('text/css', $styleDep->mimeType);
});


test('Production mode - CSS entry point', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest.json');
	$asset = $mapper->getAsset('src/styles.css');

	Assert::same('https://example.com/assets/styles-9i0j1k2l.css', $asset->url);
});


test('Fallback to filesystem when asset not found in manifest', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest.json');

	// image.gif exists on filesystem but not in the manifest
	$asset = $mapper->getAsset('image.gif');

	Assert::same('https://example.com/image.gif', $asset->url);
	Assert::same('image/gif', $asset->mimeType);
	Assert::same(__DIR__ . '/fixtures/image.gif', $asset->file);
});


test('Asset not found in manifest or filesystem', function (): void {
	$manifestPath = __DIR__ . '/fixtures/manifest.json';

	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', $manifestPath);

	Assert::exception(
		function () use ($mapper): void {
			$mapper->getAsset('non-existent.js');
		},
		Nette\Assets\AssetNotFoundException::class,
		"File 'non-existent.js' not found in Vite manifest or at path: '" . __DIR__ . "/fixtures/non-existent.js'",
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
