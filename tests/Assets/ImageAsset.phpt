<?php

declare(strict_types=1);

use Nette\Assets\ImageAsset;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('Image dimensions are detected with file', function () {
	$asset = new ImageAsset('', file: __DIR__ . '/fixtures/image.gif');

	Assert::type(Nette\Assets\ImageAsset::class, $asset);
	Assert::same(176, $asset->width);
	Assert::same(104, $asset->height);
});


test('Image dimensions overwrite', function () {
	$asset = new ImageAsset('', file: __DIR__ . '/fixtures/image.gif', width: 100);

	Assert::type(Nette\Assets\ImageAsset::class, $asset);
	Assert::same(100, $asset->width);
	Assert::same(null, $asset->height);
});


test('Invalid image dimensions throws', function () {
	$asset = new ImageAsset('', file: __DIR__ . '/fixtures/invalid.gif');

	Assert::error(
		fn() => $asset->width,
		E_NOTICE,
		'getimagesize(): Error reading from %a%',
	);
	Assert::null($asset->height);
});

test('Image dimensions are null without file', function () {
	$asset = new ImageAsset('');

	Assert::type(Nette\Assets\ImageAsset::class, $asset);
	Assert::same(null, $asset->width);
	Assert::same(null, $asset->height);
});
