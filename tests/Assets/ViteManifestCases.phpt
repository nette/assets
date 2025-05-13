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

	assertAssetEquals(new ImageAsset('https://example.com/bg-DMG1l4Bk.png'), $asset);
});


test('SCSS entry returns StyleAsset with correct path', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest3.json');
	$asset = $mapper->getAsset('assets/css/foo.scss');

	assertAssetEquals(new StyleAsset('https://example.com/foo-CU7deJlC.css'), $asset);
});


test('JS entry without dependencies returns FileAsset and can be found by name', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest4.json');

	// Access by path
	$assetByPath = $mapper->getAsset('assets/admin.js');
	assertAssetEquals(new ScriptAsset('https://example.com/admin-BrZXlwf9.js'), $assetByPath);

	// Access by name
	$assetByName = $mapper->getAsset('admin');
	Assert::equal($assetByPath, $assetByName);
});


test('JS entry with CSS returns EntryAsset with imports', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest5.json');

	// Access by path
	$asset = $mapper->getAsset('assets/admin.js');
	assertAssetEquals(new EntryAsset('https://example.com/admin-DDCqmGQL.js'), $asset);

	// Verify dependency types and urls directly using our helper
	Assert::count(2, $asset->dependencies);
	assertAssetEquals(new StyleAsset('https://example.com/admin--djP3Xwo.css'), $asset->dependencies[0]);
	assertAssetEquals(new StyleAsset('https://example.com/foo-B2r9mFhI.css'), $asset->dependencies[1]);

	// Access by name
	$assetByName = $mapper->getAsset('admin');
	Assert::equal($asset, $assetByName);
});


test('Complex entry with imports and nested CSS', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest6.json');

	$asset = $mapper->getAsset('assets/admin.js');
	assertAssetEquals(new EntryAsset('https://example.com/admin.js'), $asset);

	// Verify dependency types and urls directly using our helper
	Assert::count(3, $asset->dependencies);
	assertAssetEquals(new StyleAsset('https://example.com/admin--djP3Xwo.css'), $asset->dependencies[0]);
	assertAssetEquals(new ScriptAsset('https://example.com/foo-90X4-T0t.js'), $asset->dependencies[1]);
	assertAssetEquals(new StyleAsset('https://example.com/foo-B2r9mFhI.css'), $asset->dependencies[2]);

	// Access by name
	$assetByName = $mapper->getAsset('admin');
	assertAssetEquals(new EntryAsset('https://example.com/admin.js'), $assetByName);
});


test('Entry with direct imports', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest7.json');

	$asset = $mapper->getAsset('assets/admin.js');
	assertAssetEquals(new EntryAsset('https://example.com/admin.js'), $asset);

	// Verify dependency types and urls directly using our helper
	Assert::count(2, $asset->dependencies);
	assertAssetEquals(new StyleAsset('https://example.com/admin--djP3Xwo.css'), $asset->dependencies[0]);
	assertAssetEquals(new ScriptAsset('https://example.com/foo-KXjOppzC.js'), $asset->dependencies[1]);
});


test('Deeply nested recursive imports in chunks', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest8.json');

	// Access by path
	$asset = $mapper->getAsset('assets/deeply-nested.js');
	assertAssetEquals(new EntryAsset('https://example.com/deeply-nested-ggg777.js'), $asset);

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
	Assert::equal($asset, $assetByName);
});


test('Entry with dynamic imports', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest9.json');

	// Access the main entry by path
	$asset = $mapper->getAsset('views/bar.js');
	assertAssetEquals(new EntryAsset('https://example.com/assets/bar-gkvgaI9m.js'), $asset);

	// Check all imports - should not include dynamic imports
	Assert::count(1, $asset->dependencies);
	assertAssetEquals(new ScriptAsset('https://example.com/assets/shared-B7PI925R.js'), $asset->dependencies[0]);

	// Access by name
	$assetByName = $mapper->getAsset('bar');
	Assert::equal($assetByName, $asset);

	// Access dynamic entry directly
	$dynamicAsset = $mapper->getAsset('baz.js');
	assertAssetEquals(new ScriptAsset('https://example.com/assets/baz-B2H3sXNv.js'), $dynamicAsset);

	// Access dynamic entry by name
	$dynamicAssetByName = $mapper->getAsset('baz');
	assertAssetEquals(new ScriptAsset('https://example.com/assets/baz-B2H3sXNv.js'), $dynamicAssetByName);
});


test('Entry with circular dependencies', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest10.json');

	// Access the entry point with circular dependencies
	$asset = $mapper->getAsset('assets/nette.js');
	assertAssetEquals(new EntryAsset('https://example.com/_nette.js'), $asset);

	// Verify dependency types and urls directly using our helper
	Assert::count(2, $asset->dependencies);
	assertAssetEquals(new StyleAsset('https://example.com/_nette.css'), $asset->dependencies[0]);
	assertAssetEquals(new ScriptAsset('https://example.com/ace-BJo1PSDc.js'), $asset->dependencies[1]);

	// Access by name
	$assetByName = $mapper->getAsset('_nette');
	Assert::equal($asset, $assetByName);
});
