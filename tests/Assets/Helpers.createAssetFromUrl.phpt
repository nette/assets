<?php

declare(strict_types=1);

use Nette\Assets\Helpers;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('basic types', function () {
	$asset = Helpers::createAssetFromUrl('/fonts/test.mp3');
	Assert::type(Nette\Assets\AudioAsset::class, $asset);

	$asset = Helpers::createAssetFromUrl('/fonts/test.mp4');
	Assert::type(Nette\Assets\VideoAsset::class, $asset);

	$asset = Helpers::createAssetFromUrl('/fonts/test.js');
	Assert::type(Nette\Assets\ScriptAsset::class, $asset);

	$asset = Helpers::createAssetFromUrl('/fonts/test.css');
	Assert::type(Nette\Assets\StyleAsset::class, $asset);

	$asset = Helpers::createAssetFromUrl('/fonts/test.webp');
	Assert::type(Nette\Assets\ImageAsset::class, $asset);

	$asset = Helpers::createAssetFromUrl('/fonts/test.pdf');
	Assert::type(Nette\Assets\GenericAsset::class, $asset);

	$asset = Helpers::createAssetFromUrl('/fonts/test.woff');
	Assert::type(Nette\Assets\FontAsset::class, $asset);

	$asset = Helpers::createAssetFromUrl('/fonts/test.woff2');
	Assert::type(Nette\Assets\FontAsset::class, $asset);
});


test('Basic asset properties', function () {
	$asset = Helpers::createAssetFromUrl('http://example.com/image.gif', __DIR__ . '/fixtures/image.gif');

	Assert::same('http://example.com/image.gif', $asset->url);
	Assert::same(__DIR__ . '/fixtures/image.gif', $asset->file);
	Assert::same('http://example.com/image.gif', (string) $asset);
});
