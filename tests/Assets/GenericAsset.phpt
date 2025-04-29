<?php

declare(strict_types=1);

use Nette\Assets\GenericAsset;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('MIME type is detected with file', function () {
	$asset = new GenericAsset('', file: __DIR__ . '/fixtures/audio.mp3');
	Assert::same('audio/mpeg', $asset->mimeType);
});

test('MIME type is null without file', function () {
	$asset = new GenericAsset('');
	Assert::null($asset->mimeType);
});
