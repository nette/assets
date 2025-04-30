<?php

declare(strict_types=1);

use Nette\Assets\AudioAsset;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('Duration is detected with sourcePath', function () {
	$asset = new AudioAsset('', sourcePath: __DIR__ . '/fixtures/audio.mp3');
	Assert::same(149.45, round($asset->duration, 2));
});

test('Duration is null without sourcePath', function () {
	$asset = new AudioAsset('');
	Assert::null($asset->duration);
});
