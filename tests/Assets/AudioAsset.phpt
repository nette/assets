<?php

declare(strict_types=1);

use Nette\Assets\AudioAsset;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('Duration is detected with file', function () {
	$asset = new AudioAsset('', file: __DIR__ . '/fixtures/audio.mp3');
	Assert::same(149.45, round($asset->duration, 2));
});


test('Duration is null without file', function () {
	$asset = new AudioAsset('');
	Assert::null($asset->duration);
});


test('Invalid MP3 file throws exception', function () {
	$asset = new AudioAsset('', file: __DIR__ . '/fixtures/invalid.mp3');

	Assert::exception(
		fn() => $asset->duration,
		RuntimeException::class,
		'Failed to find MP3 frame sync bits.',
	);
});
