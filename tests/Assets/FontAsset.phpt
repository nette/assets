<?php

declare(strict_types=1);

use Nette\Assets\FontAsset;
use Nette\Utils\Html;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('FontAsset->getImportElement()', function () {
	$asset = new FontAsset(
		url: '/fonts/roboto.woff2',
		mimeType: 'font/woff2',
		integrity: 'sha256-abc123',
	);

	Assert::equal(Html::el('link', [
		'rel' => 'preload',
		'href' => '/fonts/roboto.woff2',
		'as' => 'font',
		'type' => 'font/woff2',
		'crossorigin' => true,
		'integrity' => 'sha256-abc123',
	]), $asset->getImportElement());

	// Test without integrity
	$asset = new FontAsset('/fonts/roboto.woff2', mimeType: 'font/woff2');

	Assert::equal(Html::el('link', [
		'rel' => 'preload',
		'href' => '/fonts/roboto.woff2',
		'as' => 'font',
		'type' => 'font/woff2',
		'crossorigin' => true,
	]), $asset->getImportElement());
});


test('FontAsset->getHtmlPreloadElement()', function () {
	$asset = new FontAsset('/fonts/roboto.woff2', mimeType: 'font/woff2');

	Assert::equal(Html::el('link', [
		'rel' => 'preload',
		'href' => '/fonts/roboto.woff2',
		'as' => 'font',
		'type' => 'font/woff2',
		'crossorigin' => true,
	]), $asset->getPreloadElement());
});
