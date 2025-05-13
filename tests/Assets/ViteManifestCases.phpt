<?php

declare(strict_types=1);

use Nette\Assets\AssetNotFoundException;
use Nette\Assets\EntryAsset;
use Nette\Assets\ImageAsset;
use Nette\Assets\ScriptAsset;
use Nette\Assets\StyleAsset;
use Nette\Assets\ViteMapper;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


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

	$assetByPath = $mapper->getAsset('assets/admin.js');
	Assert::type(ScriptAsset::class, $assetByPath);
	Assert::same('https://example.com/admin-BrZXlwf9.js', $assetByPath->url);
});


test('JS entry with CSS returns EntryAsset with imports', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest5.json');

	$asset = $mapper->getAsset('assets/admin.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('https://example.com/admin-DDCqmGQL.js', $asset->url);

	// Verify dependency types and urls directly using our helper
	Assert::count(0, $asset->preloads);
	assertAssets([
		new StyleAsset('https://example.com/admin--djP3Xwo.css', mimeType: 'text/css', file: __DIR__ . '/fixtures/admin--djP3Xwo.css', crossorigin: true),
		new StyleAsset('https://example.com/foo-B2r9mFhI.css', mimeType: 'text/css', file: __DIR__ . '/fixtures/foo-B2r9mFhI.css', crossorigin: true),
	], $asset->imports);
});


test('Complex entry with imports and nested CSS', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest6.json');

	$asset = $mapper->getAsset('assets/admin.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('https://example.com/admin.js', $asset->url);

	// Verify dependency types and urls directly using our helper
	assertAssets([
		new StyleAsset('https://example.com/admin--djP3Xwo.css', mimeType: 'text/css', file: __DIR__ . '/fixtures/admin--djP3Xwo.css', crossorigin: true),
		new StyleAsset('https://example.com/foo-B2r9mFhI.css', mimeType: 'text/css', file: __DIR__ . '/fixtures/foo-B2r9mFhI.css', crossorigin: true),
	], $asset->imports);

	assertAssets([
		new ScriptAsset('https://example.com/foo-90X4-T0t.js', mimeType: 'application/javascript', file: __DIR__ . '/fixtures/foo-90X4-T0t.js', type: 'module', crossorigin: true),
	], $asset->preloads);
});


test('Entry with direct imports', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest7.json');

	$asset = $mapper->getAsset('assets/admin.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('https://example.com/admin.js', $asset->url);

	// Verify dependency types and urls directly using our helper
	assertAssets([
		new StyleAsset('https://example.com/admin--djP3Xwo.css', mimeType: 'text/css', file: __DIR__ . '/fixtures/admin--djP3Xwo.css', crossorigin: true),
	], $asset->imports);

	assertAssets([
		new ScriptAsset('https://example.com/foo-KXjOppzC.js', mimeType: 'application/javascript', file: __DIR__ . '/fixtures/foo-KXjOppzC.js', type: 'module', crossorigin: true),
	], $asset->preloads);
});


test('Deeply nested recursive imports in chunks', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest8.json');

	$asset = $mapper->getAsset('assets/deeply-nested.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('https://example.com/deeply-nested-ggg777.js', $asset->url);

	// Verify dependency types and urls directly using our helper
	assertAssets([
		new StyleAsset('https://example.com/main-styles-hhh888.css', mimeType: 'text/css', file: __DIR__ . '/fixtures/main-styles-hhh888.css', crossorigin: true),
		new StyleAsset('https://example.com/level1-styles-fff666.css', mimeType: 'text/css', file: __DIR__ . '/fixtures/level1-styles-fff666.css', crossorigin: true),
		new StyleAsset('https://example.com/level2-styles-ddd444.css', mimeType: 'text/css', file: __DIR__ . '/fixtures/level2-styles-ddd444.css', crossorigin: true),
		new StyleAsset('https://example.com/level3-styles-bbb222.css', mimeType: 'text/css', file: __DIR__ . '/fixtures/level3-styles-bbb222.css', crossorigin: true),
	], $asset->imports);

	assertAssets([
		new ScriptAsset('https://example.com/level1-chunk-eee555.js', mimeType: 'application/javascript', file: __DIR__ . '/fixtures/level1-chunk-eee555.js', type: 'module', crossorigin: true),
		new ScriptAsset('https://example.com/level2-chunk-ccc333.js', mimeType: 'application/javascript', file: __DIR__ . '/fixtures/level2-chunk-ccc333.js', type: 'module', crossorigin: true),
		new ScriptAsset('https://example.com/level3-chunk-aaa111.js', mimeType: 'application/javascript', file: __DIR__ . '/fixtures/level3-chunk-aaa111.js', type: 'module', crossorigin: true),
	], $asset->preloads);
});


test('Entry with dynamic imports', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest9.json');

	$asset = $mapper->getAsset('views/bar.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('https://example.com/assets/bar-gkvgaI9m.js', $asset->url);

	// Check all imports - should not include dynamic imports
	Assert::count(0, $asset->imports);
	assertAssets([
		new ScriptAsset('https://example.com/assets/shared-B7PI925R.js', mimeType: 'application/javascript', file: __DIR__ . '/fixtures/assets/shared-B7PI925R.js', type: 'module', crossorigin: true),
	], $asset->preloads);

	// Access dynamic entry directly
	$dynamicAsset = $mapper->getAsset('baz.js');
	Assert::type(ScriptAsset::class, $dynamicAsset);
	Assert::same('https://example.com/assets/baz-B2H3sXNv.js', $dynamicAsset->url);
});


test('Entry with circular dependencies', function (): void {
	$mapper = new ViteMapper('https://example.com', __DIR__ . '/fixtures', __DIR__ . '/fixtures/manifest10.json');

	// Access the entry point with circular dependencies
	$asset = $mapper->getAsset('assets/nette.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('https://example.com/_nette.js', $asset->url);

	// Verify dependency types and urls directly using our helper
	assertAssets([
		new StyleAsset('https://example.com/_nette.css', mimeType: 'text/css', file: __DIR__ . '/fixtures/_nette.css', crossorigin: true),
	], $asset->imports);
	assertAssets([
		new ScriptAsset('https://example.com/ace-BJo1PSDc.js', mimeType: 'application/javascript', file: __DIR__ . '/fixtures/ace-BJo1PSDc.js', type: 'module', crossorigin: true),
	], $asset->preloads);
});
