<?php

declare(strict_types=1);

use Nette\Assets\VideoAsset;
use Nette\Utils\Html;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('getHtmlElement()', function () {
	$asset = new VideoAsset('/video/movie.mp4', 'video/mp4', null, 1920, 1080, 120.5, '/img/poster.jpg', true);

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
	$asset = new VideoAsset('/video/movie.mp4', 'video/mp4');

	Assert::equal(Html::el('link', [
		'rel' => 'preload',
		'href' => '/video/movie.mp4',
		'as' => 'video',
		'type' => 'video/mp4',
	]), $asset->getPreloadElement());
});
