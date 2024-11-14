<?php

declare(strict_types=1);

use Nette\Assets\FileAsset;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('Basic asset properties', function () {
	$asset = new FileAsset('http://example.com/image.gif', __DIR__ . '/fixtures/image.gif');

	Assert::same('http://example.com/image.gif', $asset->getUrl());
	Assert::same(__DIR__ . '/fixtures/image.gif', $asset->getPath());
	Assert::same('http://example.com/image.gif', (string) $asset);
});

test('Non-existent file', function () {
	$asset = new FileAsset('http://example.com/missing.jpg', '/non/existent/path');
});

test('Image dimensions', function () {
	$asset = new FileAsset('http://example.com/image.gif', __DIR__ . '/fixtures/image.gif');

	Assert::same(176, $asset->getWidth());
	Assert::same(104, $asset->getHeight());
});

test('Invalid image dimensions throws', function () {
	$asset = new FileAsset('http://example.com/audio.mp3', __DIR__ . '/fixtures/audio.mp3');

	Assert::exception(
		fn() => $asset->getWidth(),
		RuntimeException::class,
		sprintf("Cannot get size of image '%s'. %s", $asset->getPath(), Nette\Utils\Helpers::getLastError()),
	);
});

test('MP3 duration', function () {
	$asset = new FileAsset('http://example.com/audio.mp3', __DIR__ . '/fixtures/audio.mp3');

	Assert::same(149.45, round($asset->getDuration(), 2));
});

test('Invalid MP3 throws', function () {
	$asset = new FileAsset('http://example.com/image.gif', __DIR__ . '/fixtures/image.gif');

	Assert::exception(
		fn() => $asset->getDuration(),
		RuntimeException::class,
		'Failed to find MP3 frame sync bits.',
	);
});
