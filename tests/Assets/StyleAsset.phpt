<?php

declare(strict_types=1);

use Nette\Assets\StyleAsset;
use Nette\Utils\Html;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('getImportElement()', function () {
	$asset = new StyleAsset('/css/style.css', media: 'screen and (min-width: 600px)', integrity: 'sha256-abc123');

	Assert::equal(Html::el('link', [
		'rel' => 'stylesheet',
		'href' => '/css/style.css',
		'media' => 'screen and (min-width: 600px)',
		'integrity' => 'sha256-abc123',
		'crossorigin' => true,
	]), $asset->getImportElement());
});


test('getHtmlPreloadElement()', function () {
	$asset = new StyleAsset('/css/style.css');

	Assert::equal(Html::el('link', [
		'rel' => 'preload',
		'href' => '/css/style.css',
		'as' => 'style',
		'crossorigin' => false,
	]), $asset->getPreloadElement());
});
