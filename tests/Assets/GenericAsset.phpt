<?php

declare(strict_types=1);

use Nette\Assets\GenericAsset;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('MIME type is detected with sourcePath', function () {
	$asset = new GenericAsset('', sourcePath: __DIR__ . '/fixtures/audio.mp3');
	Assert::same('audio/mpeg', $asset->mimeType);
});

test('MIME type is null without sourcePath', function () {
	$asset = new GenericAsset('');
	Assert::null($asset->mimeType);
});
