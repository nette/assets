<?php

declare(strict_types=1);

use Nette\Assets\Asset;
use Nette\Assets\AssetNotFoundException;
use Nette\Assets\EntryAsset;
use Nette\Assets\ImageAsset;
use Nette\Assets\ScriptAsset;
use Nette\Assets\StyleAsset;
use Nette\Assets\ViteMapper;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


/**
 * Helper function to compare Asset objects, ignoring properties that don't matter for equality
 */
function assertAssetEquals(Asset $expected, Asset $actual): void
{
	Assert::type($expected::class, $actual);
	Assert::same($expected->url, $actual->url);
}


test('Reject direct chunk access', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest1.json');

	Assert::exception(
		function () use ($mapper): void {
			$mapper->getAsset('_foo-KXjOppzC.js');
		},
		AssetNotFoundException::class,
		"Cannot directly access internal chunk '_foo-KXjOppzC.js'",
	);
});

test('Image asset returns ImageAsset with correct path', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest2.json');
	$asset = $mapper->getAsset('assets/img/bg.png');

	Assert::type(ImageAsset::class, $asset);
	Assert::same('https://example.com/bg-DMG1l4Bk.png', $asset->url);
});

test('SCSS entry returns StyleAsset with correct path', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest3.json');
	$asset = $mapper->getAsset('assets/css/foo.scss');

	Assert::type(StyleAsset::class, $asset);
	Assert::same('https://example.com/foo-CU7deJlC.css', $asset->url);
});

test('JS entry without dependencies returns FileAsset and can be found by name', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest4.json');

	// Access by path
	$assetByPath = $mapper->getAsset('assets/admin.js');
	Assert::type(ScriptAsset::class, $assetByPath);
	Assert::same('https://example.com/admin-BrZXlwf9.js', $assetByPath->url);

	// Access by name
	$assetByName = $mapper->getAsset('admin');
	Assert::type(ScriptAsset::class, $assetByName);
	Assert::same('https://example.com/admin-BrZXlwf9.js', $assetByName->url);
});

test('JS entry with CSS returns EntryAsset with imports', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest5.json');

	// Access by path
	$asset = $mapper->getAsset('assets/admin.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('https://example.com/admin-DDCqmGQL.js', $asset->url);

	// Check CSS imports
	$dependencies = array_map('strval', $asset->dependencies);
	Assert::count(2, $dependencies);
	Assert::contains('https://example.com/admin--djP3Xwo.css', $dependencies);
	Assert::contains('https://example.com/foo-B2r9mFhI.css', $dependencies);

	// Verify dependency types and urls directly using our helper
	Assert::count(2, $asset->dependencies);

	// Create expected objects
	$expected = [
		new StyleAsset('https://example.com/admin--djP3Xwo.css'),
		new StyleAsset('https://example.com/foo-B2r9mFhI.css'),
	];

	// Compare each dependency
	assertAssetEquals($expected[0], $asset->dependencies[0]);
	assertAssetEquals($expected[1], $asset->dependencies[1]);

	// Access by name
	$assetByName = $mapper->getAsset('admin');
	Assert::type(EntryAsset::class, $assetByName);
	Assert::same('https://example.com/admin-DDCqmGQL.js', $assetByName->url);
});

test('Complex entry with imports and nested CSS', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest6.json');

	$asset = $mapper->getAsset('assets/admin.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('https://example.com/admin.js', $asset->url);

	// Check all imports: JS import and both CSS files
	$dependencies = array_map('strval', $asset->dependencies);
	Assert::count(3, $dependencies);
	Assert::contains('https://example.com/admin--djP3Xwo.css', $dependencies);
	Assert::contains('https://example.com/foo-90X4-T0t.js', $dependencies);
	Assert::contains('https://example.com/foo-B2r9mFhI.css', $dependencies);

	// Verify dependency types and urls directly using our helper
	Assert::count(3, $asset->dependencies);

	// Create expected objects
	$expected = [
		new StyleAsset('https://example.com/admin--djP3Xwo.css'),
		new ScriptAsset('https://example.com/foo-90X4-T0t.js'),
		new StyleAsset('https://example.com/foo-B2r9mFhI.css'),
	];

	// Compare each dependency
	assertAssetEquals($expected[0], $asset->dependencies[0]);
	assertAssetEquals($expected[1], $asset->dependencies[1]);
	assertAssetEquals($expected[2], $asset->dependencies[2]);

	// Access by name
	$assetByName = $mapper->getAsset('admin');
	Assert::type(EntryAsset::class, $assetByName);
	Assert::same('https://example.com/admin.js', $assetByName->url);
});

test('Entry with direct imports', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest7.json');

	$asset = $mapper->getAsset('assets/admin.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('https://example.com/admin.js', $asset->url);

	// Check all imports: both JS and CSS
	$dependencies = array_map('strval', $asset->dependencies);
	Assert::count(2, $dependencies);
	Assert::contains('https://example.com/foo-KXjOppzC.js', $dependencies);
	Assert::contains('https://example.com/admin--djP3Xwo.css', $dependencies);

	// Verify dependency types and urls directly using our helper
	Assert::count(2, $asset->dependencies);

	// Create expected objects
	$expected = [
		new StyleAsset('https://example.com/admin--djP3Xwo.css'),
		new ScriptAsset('https://example.com/foo-KXjOppzC.js'),
	];

	// Compare each dependency
	assertAssetEquals($expected[0], $asset->dependencies[0]);
	assertAssetEquals($expected[1], $asset->dependencies[1]);
});

test('Deeply nested recursive imports in chunks', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest8.json');

	// Access by path
	$asset = $mapper->getAsset('assets/deeply-nested.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('https://example.com/deeply-nested-ggg777.js', $asset->url);

	// Check all imports - should include all JS and CSS files from all levels
	$dependencies = array_map('strval', $asset->dependencies);
	Assert::count(7, $dependencies);

	// Main entry CSS
	Assert::contains('https://example.com/main-styles-hhh888.css', $dependencies);

	// Level 1 chunk and its CSS
	Assert::contains('https://example.com/level1-chunk-eee555.js', $dependencies);
	Assert::contains('https://example.com/level1-styles-fff666.css', $dependencies);

	// Level 2 chunk and its CSS
	Assert::contains('https://example.com/level2-chunk-ccc333.js', $dependencies);
	Assert::contains('https://example.com/level2-styles-ddd444.css', $dependencies);

	// Level 3 chunk and its CSS
	Assert::contains('https://example.com/level3-chunk-aaa111.js', $dependencies);
	Assert::contains('https://example.com/level3-styles-bbb222.css', $dependencies);

	// Verify dependency types and urls directly using our helper
	Assert::count(7, $asset->dependencies);

	// Create expected objects
	$expected = [
		new StyleAsset('https://example.com/main-styles-hhh888.css'),
		new ScriptAsset('https://example.com/level1-chunk-eee555.js'),
		new StyleAsset('https://example.com/level1-styles-fff666.css'),
		new ScriptAsset('https://example.com/level2-chunk-ccc333.js'),
		new StyleAsset('https://example.com/level2-styles-ddd444.css'),
		new ScriptAsset('https://example.com/level3-chunk-aaa111.js'),
		new StyleAsset('https://example.com/level3-styles-bbb222.css'),
	];

	// Compare each dependency
	for ($i = 0; $i < 7; $i++) {
		assertAssetEquals($expected[$i], $asset->dependencies[$i]);
	}

	// Access by name
	$assetByName = $mapper->getAsset('deeplyNested');
	Assert::type(EntryAsset::class, $assetByName);
	Assert::same('https://example.com/deeply-nested-ggg777.js', $assetByName->url);
	Assert::count(7, $assetByName->dependencies);
});

test('Entry with circular dependencies', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest10.json');

	// Access the entry point with circular dependencies
	$asset = $mapper->getAsset('assets/nette.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('https://example.com/_nette.js', $asset->url);

	// Check dependencies - circular reference should be detected and handled
	// The entry itself should not be included in its own dependencies
	$dependencies = array_map('strval', $asset->dependencies);

	// Should contain the CSS from the entry and the ace.js chunk
	Assert::count(2, $dependencies);
	Assert::contains('https://example.com/_nette.css', $dependencies);
	Assert::contains('https://example.com/ace-BJo1PSDc.js', $dependencies);

	// Verify dependency types and urls directly using our helper
	Assert::count(2, $asset->dependencies);

	// Create expected objects
	$expected = [
		new StyleAsset('https://example.com/_nette.css'),
		new ScriptAsset('https://example.com/ace-BJo1PSDc.js'),
	];

	// Compare each dependency
	assertAssetEquals($expected[0], $asset->dependencies[0]);
	assertAssetEquals($expected[1], $asset->dependencies[1]);

	// Access by name
	$assetByName = $mapper->getAsset('_nette');
	Assert::type(EntryAsset::class, $assetByName);
	Assert::same('https://example.com/_nette.js', $assetByName->url);
	Assert::count(2, $assetByName->dependencies);
});

/*
test('Entry with dynamic imports', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest9.json');

	// Access main entry by path
	$asset = $mapper->getAsset('views/bar.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('https://example.com/assets/bar-gkvgaI9m.js', $asset->url);

	// Check all imports - should include both regular imports and dynamic imports
	$dependencies = array_map('strval', $asset->dependencies);
	Assert::count(2, $dependencies);

	// Should contain both the shared chunk and the dynamic import
	Assert::contains('https://example.com/assets/shared-B7PI925R.js', $dependencies);
	Assert::contains('https://example.com/assets/baz-B2H3sXNv.js', $dependencies);

	// Access by name
	$assetByName = $mapper->getAsset('bar');
	Assert::type(EntryAsset::class, $assetByName);
	Assert::same('https://example.com/assets/bar-gkvgaI9m.js', $assetByName->url);
	Assert::count(2, $assetByName->dependencies);

	// Access dynamic entry directly
	$dynamicAsset = $mapper->getAsset('baz.js');
	Assert::type(ScriptAsset::class, $dynamicAsset);
	Assert::same('https://example.com/assets/baz-B2H3sXNv.js', $dynamicAsset->url);

	// Access dynamic entry by name
	$dynamicAssetByName = $mapper->getAsset('baz');
	Assert::type(ScriptAsset::class, $dynamicAssetByName);
	Assert::same('https://example.com/assets/baz-B2H3sXNv.js', $dynamicAssetByName->url);
});
*/
