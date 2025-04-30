<?php

declare(strict_types=1);

use Nette\Assets\Helpers;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('basic types', function () {
	$asset = Helpers::createAssetFromUrl('/fonts/test.mp3', null);
	Assert::type(Nette\Assets\AudioAsset::class, $asset);

	$asset = Helpers::createAssetFromUrl('/fonts/test.mp4', null);
	Assert::type(Nette\Assets\VideoAsset::class, $asset);

	$asset = Helpers::createAssetFromUrl('/fonts/test.js', null);
	Assert::type(Nette\Assets\ScriptAsset::class, $asset);

	$asset = Helpers::createAssetFromUrl('/fonts/test.css', null);
	Assert::type(Nette\Assets\StyleAsset::class, $asset);

	$asset = Helpers::createAssetFromUrl('/fonts/test.webp', null);
	Assert::type(Nette\Assets\ImageAsset::class, $asset);

	$asset = Helpers::createAssetFromUrl('/fonts/test.pdf', null);
	Assert::type(Nette\Assets\GenericAsset::class, $asset);

	$asset = Helpers::createAssetFromUrl('/fonts/test.woff', null);
	Assert::type(Nette\Assets\FontAsset::class, $asset);

	$asset = Helpers::createAssetFromUrl('/fonts/test.woff2', null);
	Assert::type(Nette\Assets\FontAsset::class, $asset);
});


test('Basic asset properties', function () {
	$asset = Helpers::createAssetFromUrl('http://example.com/image.gif', __DIR__ . '/fixtures/image.gif');

	Assert::same('http://example.com/image.gif', $asset->url);
	Assert::same(__DIR__ . '/fixtures/image.gif', $asset->file);
	Assert::same('http://example.com/image.gif', (string) $asset);
});
