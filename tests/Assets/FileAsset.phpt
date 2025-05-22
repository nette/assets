<?php

declare(strict_types=1);

use Nette\Assets\FileAsset;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('Basic asset properties', function () {
	$asset = new FileAsset('http://example.com/image.gif', __DIR__ . '/fixtures/image.gif');

	Assert::same('http://example.com/image.gif', $asset->url);
	Assert::same(__DIR__ . '/fixtures/image.gif', $asset->file);
	Assert::same('http://example.com/image.gif', (string) $asset);
});

test('Non-existent file', function () {
	$asset = new FileAsset('http://example.com/missing.jpg', '/non/existent/path');
});

test('Image dimensions', function () {
	$asset = new FileAsset('http://example.com/image.gif', __DIR__ . '/fixtures/image.gif');

	Assert::same(176, $asset->width);
	Assert::same(104, $asset->height);
});

test('Invalid image dimensions throws', function () {
	$asset = new FileAsset('http://example.com/audio.mp3', __DIR__ . '/fixtures/audio.mp3');

	Assert::exception(
		fn() => $asset->width,
		RuntimeException::class,
		sprintf("Cannot get size of image '%s'. %s", $asset->file, Nette\Utils\Helpers::getLastError()),
	);
});

test('MP3 duration', function () {
	$asset = new FileAsset('http://example.com/audio.mp3', __DIR__ . '/fixtures/audio.mp3');

	Assert::same(149.45, round($asset->duration, 2));
});

test('Invalid MP3 throws', function () {
	$asset = new FileAsset('http://example.com/image.gif', __DIR__ . '/fixtures/image.gif');

	Assert::exception(
		fn() => $asset->duration,
		RuntimeException::class,
		'Failed to find MP3 frame sync bits.',
	);
});
