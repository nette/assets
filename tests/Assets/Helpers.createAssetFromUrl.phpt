<?php

declare(strict_types=1);

use Nette\Assets\Helpers;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('Basic asset properties', function () {
	$asset = Helpers::createAssetFromUrl('http://example.com/image.gif', __DIR__ . '/fixtures/image.gif');

	Assert::same('http://example.com/image.gif', $asset->url);
	Assert::same(__DIR__ . '/fixtures/image.gif', $asset->sourcePath);
	Assert::same('http://example.com/image.gif', (string) $asset);
});

test('Non-existent file', function () {
	$asset = Helpers::createAssetFromUrl('image.gif', '/non/existent/path.gif');

	Assert::type(Nette\Assets\ImageAsset::class, $asset);
});

test('Image dimensions', function () {
	$asset = Helpers::createAssetFromUrl('image.gif', __DIR__ . '/fixtures/image.gif');

	Assert::type(Nette\Assets\ImageAsset::class, $asset);
	Assert::same(176, $asset->width);
	Assert::same(104, $asset->height);
});

test('Invalid image dimensions throws', function () {
	$asset = Helpers::createAssetFromUrl('image.gif', __DIR__ . '/fixtures/invalid.gif');

	Assert::type(Nette\Assets\ImageAsset::class, $asset);

	Assert::error(
		fn() => $asset->width,
		E_NOTICE,
		'getimagesize(): Error reading from %a%',
	);
	Assert::null($asset->height);
});

test('MP3 duration', function () {
	$asset = Helpers::createAssetFromUrl('audio.mp3', __DIR__ . '/fixtures/audio.mp3');

	Assert::type(Nette\Assets\AudioAsset::class, $asset);
	Assert::same(149.45, round($asset->duration, 2));
});

test('Invalid MP3 throws', function () {
	$asset = Helpers::createAssetFromUrl('audio.mp3', __DIR__ . '/fixtures/invalid.mp3');

	Assert::exception(
		fn() => $asset->duration,
		RuntimeException::class,
		'Failed to find MP3 frame sync bits.',
	);
});
