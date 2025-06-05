<?php

declare(strict_types=1);

use Nette\Assets\AudioAsset;
use Nette\Utils\Html;
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


test('getImportElement()', function () {
	$asset = new AudioAsset('/audio/sound.mp3', mimeType: 'audio/mpeg', duration: 120.5);

	Assert::equal(Html::el('audio', [
		'src' => '/audio/sound.mp3',
		'type' => 'audio/mpeg',
	]), $asset->getImportElement());
});


test('getHtmlPreloadElement()', function () {
	$asset = new AudioAsset('/audio/sound.mp3', mimeType: 'audio/mpeg');

	Assert::equal(Html::el('link', [
		'rel' => 'preload',
		'href' => '/audio/sound.mp3',
		'as' => 'audio',
		'type' => 'audio/mpeg',
	]), $asset->getPreloadElement());
});
