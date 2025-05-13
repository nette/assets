<?php

declare(strict_types=1);

use Nette\Assets\EntryAsset;
use Nette\Utils\Html;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('getHtmlElement()', function () {
	$asset = new EntryAsset('/js/script.js', 'application/javascript', integrity: 'sha256-abc123');

	Assert::equal(Html::el('script', [
		'src' => '/js/script.js',
		'type' => 'module',
		'integrity' => 'sha256-abc123',
		'crossorigin' => true,
	]), $asset->getImportElement());
});


test('getHtmlPreloadElement()', function () {
	$asset = new EntryAsset('/js/module.js', 'application/javascript', type: 'module');

	Assert::equal(Html::el('link', [
		'rel' => 'modulepreload',
		'href' => '/js/module.js',
	]), $asset->getPreloadElement());
});
