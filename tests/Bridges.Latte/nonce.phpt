<?php

declare(strict_types=1);

use Nette\Assets\EntryAsset;
use Nette\Assets\ImageAsset;
use Nette\Assets\Registry;
use Nette\Assets\ScriptAsset;
use Nette\Assets\StyleAsset;
use Nette\Bridges\AssetsLatte\Runtime;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('StyleAsset: nonce is added', function () {
	$asset = new StyleAsset('style.css');
	$runtime = new Runtime(Mockery::mock(Registry::class), 'abc123');
	Assert::contains('nonce="abc123"', $runtime->renderAsset($asset));
	Assert::contains('nonce="abc123"', $runtime->renderAssetPreload($asset));
	Assert::contains('nonce="abc123"', $runtime->renderAttributes($asset, 'link', []));
});


test('ScriptAsset: nonce is added', function () {
	$asset = new ScriptAsset('style.css');
	$runtime = new Runtime(Mockery::mock(Registry::class), 'abc123');
	Assert::contains('nonce="abc123"', $runtime->renderAsset($asset));
	Assert::contains('nonce="abc123"', $runtime->renderAssetPreload($asset));
	Assert::contains('nonce="abc123"', $runtime->renderAttributes($asset, 'script', []));
});


test('EntryAsset: nonce is added', function () {
	$asset = new EntryAsset(
		'style.js',
		imports: [new StyleAsset('imported.css')],
		preloads: [new ScriptAsset('imported.js')],
	);

	$runtime = new Runtime(Mockery::mock(Registry::class), 'abc123');
	Assert::same(
		'<script src="style.js" type="module" nonce="abc123"></script><link rel="preload" href="imported.js" as="script" nonce="abc123"><link rel="stylesheet" href="imported.css" nonce="abc123">',
		$runtime->renderAsset($asset),
	);
	Assert::contains('nonce="abc123"', $runtime->renderAssetPreload($asset));
	Assert::contains('nonce="abc123"', $runtime->renderAttributes($asset, 'script', []));
});


test('ImageAsset: nonce is NOT added', function () {
	$asset = new ImageAsset('https://example.com/image.jpg');
	$runtime = new Runtime(Mockery::mock(Registry::class), 'abc123');
	Assert::notContains('nonce', $runtime->renderAsset($asset));
	Assert::contains('nonce', $runtime->renderAssetPreload($asset)); // is unnecessary here
	Assert::notContains('nonce', $runtime->renderAttributes($asset, 'img', []));
});
