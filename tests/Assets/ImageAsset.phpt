<?php

declare(strict_types=1);

use Nette\Assets\ImageAsset;
use Nette\Utils\Html;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('Image dimensions are detected with file', function () {
	$asset = new ImageAsset('', file: __DIR__ . '/fixtures/image.gif'); // in fact it is PNG

	Assert::type(Nette\Assets\ImageAsset::class, $asset);
	Assert::same(176, $asset->width);
	Assert::same(104, $asset->height);
	Assert::same('image/png', $asset->mimeType);
});


test('Image dimensions are detected and are divided by the density', function () {
	$asset = new ImageAsset('', file: __DIR__ . '/fixtures/image.gif', density: 5);

	Assert::type(Nette\Assets\ImageAsset::class, $asset);
	Assert::same(35, $asset->width);
	Assert::same(21, $asset->height);
	Assert::same('image/png', $asset->mimeType);
});


test('Image dimensions overwrite', function () {
	$asset = new ImageAsset('', file: __DIR__ . '/fixtures/image.gif', width: 100);

	Assert::type(Nette\Assets\ImageAsset::class, $asset);
	Assert::same(100, $asset->width);
	Assert::same(null, $asset->height);
	Assert::same('image/png', $asset->mimeType);
});


test('Invalid image dimensions throws', function () {
	$asset = new ImageAsset('', file: __DIR__ . '/fixtures/invalid.gif');

	Assert::error(
		fn() => $asset->width,
		E_NOTICE,
		'getimagesize(): Error reading from %a%',
	);
	Assert::null($asset->height);
	Assert::same(null, $asset->mimeType);
});


test('Image dimensions are null without file', function () {
	$asset = new ImageAsset('');

	Assert::type(Nette\Assets\ImageAsset::class, $asset);
	Assert::same(null, $asset->width);
	Assert::same(null, $asset->height);
	Assert::same(null, $asset->mimeType);
});


test('getImportElement()', function () {
	$asset = new ImageAsset(
		'/img/image.jpg',
		width: 800,
		height: 600,
		mimeType: 'image/jpeg',
		alternative: 'Image description',
		lazyLoad: true,
	);

	Assert::equal(Html::el('img', [
		'src' => '/img/image.jpg',
		'width' => '800',
		'height' => '600',
		'alt' => 'Image description',
		'loading' => 'lazy',
	]), $asset->getImportElement());
});


test('getHtmlPreloadElement()', function () {
	$asset = new ImageAsset('/img/image.jpg', mimeType: 'image/jpeg');

	Assert::equal(Html::el('link', [
		'rel' => 'preload',
		'href' => '/img/image.jpg',
		'as' => 'image',
		'type' => 'image/jpeg',
	]), $asset->getPreloadElement());
});
