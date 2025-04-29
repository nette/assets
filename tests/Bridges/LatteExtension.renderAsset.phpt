<?php

declare(strict_types=1);

use Nette\Assets\Asset;
use Nette\Assets\EntryAsset;
use Nette\Assets\Registry;
use Nette\Assets\ScriptAsset;
use Nette\Assets\StyleAsset;
use Nette\Bridges\Assets\LatteExtension;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// Prepare mock Registry and assets
class MockRegistry extends Registry
{
	private array $assets = [];


	public function mockAddAsset(string $ref, Asset $asset): void
	{
		$this->assets[$ref] = $asset;
	}


	public function getAsset(string|array $qualifiedRef, array $options = []): Asset
	{
		if (is_string($qualifiedRef)) {
			[$mapper, $ref] = explode(':', $qualifiedRef, 2);
			return $this->assets[$ref] ?? throw new Nette\Assets\AssetNotFoundException("Asset not found: $ref");
		}
		throw new InvalidArgumentException('Array form not supported in mock');
	}


	public function tryGetAsset(string|array $qualifiedRef, array $options = []): ?Asset
	{
		try {
			return $this->getAsset($qualifiedRef, $options);
		} catch (Nette\Assets\AssetNotFoundException) {
			return null;
		}
	}
}

// Test rendering JS entry asset in production mode
test('Render JS entry asset with imports in production mode', function (): void {
	$mockRegistry = new MockRegistry();

	// Create EntryAsset representing the main JS entry point with CSS
	$jsMainAsset = new EntryAsset(
		url: 'https://example.com/assets/main-1a2b3c4d.js',
		mimeType: 'application/javascript',
		dependencies: [
			new ScriptAsset(
				url: 'https://example.com/assets/shared-5e6f7g8h.js',
				mimeType: 'application/javascript',
				integrity: 'sha384-hash123',
			),
			new StyleAsset(
				url: 'https://example.com/assets/main-a1b2c3d4.css',
				mimeType: 'text/css',
				media: 'screen',
			),
		],
		sourcePath: '/path/to/assets/main-1a2b3c4d.js',
	);

	$mockRegistry->mockAddAsset('main.js', $jsMainAsset);

	$latteExtension = new LatteExtension($mockRegistry);

	// Render the asset tags
	$output = $latteExtension->renderAsset('mapper:main.js');

	// Verify the output contains all necessary tags
	Assert::contains('<script src="https://example.com/assets/main-1a2b3c4d.js" type="module"></script>', $output);
	Assert::contains('<script src="https://example.com/assets/main-1a2b3c4d.js" type="module"></script>'
		. '<link rel="modulepreload" src="https://example.com/assets/shared-5e6f7g8h.js"></script>'
		. '<link rel="stylesheet" href="https://example.com/assets/main-a1b2c3d4.css" media="screen">', $output);
	Assert::contains('<link rel="stylesheet" href="https://example.com/assets/main-a1b2c3d4.css" media="screen">', $output);

	// Verify the output does not contain Vite client script in production
	Assert::notContains('@vite/client', $output);
});

// Test rendering CSS asset
test('Render Style asset', function (): void {
	$mockRegistry = new MockRegistry();

	// Create StyleAsset for CSS
	$cssAsset = new StyleAsset(
		url: 'https://example.com/assets/styles-9i0j1k2l.css',
		mimeType: 'text/css',
		sourcePath: '/path/to/assets/styles-9i0j1k2l.css',
		media: 'print',
		integrity: 'sha384-css123',
	);

	$mockRegistry->mockAddAsset('styles.css', $cssAsset);

	$latteExtension = new LatteExtension($mockRegistry);

	// Render the asset tags
	$output = $latteExtension->renderAsset('mapper:styles.css');

	// Verify the output contains CSS link with attributes
	Assert::contains('<link rel="stylesheet" href="https://example.com/assets/styles-9i0j1k2l.css" media="print" integrity="sha384-css123" crossorigin="anonymous">', $output);
});

// Test rendering Script asset
test('Render Script asset', function (): void {
	$mockRegistry = new MockRegistry();

	// Create ScriptAsset for JS
	$jsAsset = new ScriptAsset(
		url: 'https://example.com/assets/simple-script.js',
		mimeType: 'application/javascript',
		module: true,
		sourcePath: '/path/to/assets/simple-script.js',
		integrity: 'sha384-js456',
	);

	$mockRegistry->mockAddAsset('simple.js', $jsAsset);

	$latteExtension = new LatteExtension($mockRegistry);

	// Render the asset tags
	$output = $latteExtension->renderAsset('mapper:simple.js');

	// Verify the output contains JS tag with integrity
	Assert::contains('<script src="https://example.com/assets/simple-script.js" type="module" integrity="sha384-js456" crossorigin="anonymous"></script>', $output);
});
