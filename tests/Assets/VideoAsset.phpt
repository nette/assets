<?php

declare(strict_types=1);

use Nette\Assets\VideoAsset;
use Nette\Utils\Html;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('getImportElement()', function () {
	$asset = new VideoAsset(
		'/video/movie.mp4',
		mimeType: 'video/mp4',
		file: null,
		width: 1920,
		height: 1080,
		duration: 120.5,
		poster: '/img/poster.jpg',
		autoPlay: true,
	);

	Assert::equal(Html::el('video', [
		'src' => '/video/movie.mp4',
		'width' => '1920',
		'height' => '1080',
		'type' => 'video/mp4',
		'poster' => '/img/poster.jpg',
		'autoplay' => true,
	]), $asset->getImportElement());
});


test('getHtmlPreloadElement()', function () {
	$asset = new VideoAsset('/video/movie.mp4', mimeType: 'video/mp4');

	Assert::equal(Html::el('link', [
		'rel' => 'preload',
		'href' => '/video/movie.mp4',
		'as' => 'video',
		'type' => 'video/mp4',
	]), $asset->getPreloadElement());
});
