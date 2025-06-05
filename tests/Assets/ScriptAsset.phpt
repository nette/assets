<?php

declare(strict_types=1);

use Nette\Assets\ScriptAsset;
use Nette\Utils\Html;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('getImportElement()', function () {
	$asset = new ScriptAsset('/js/script.js', integrity: 'sha256-abc123');

	Assert::equal(Html::el('script', [
		'src' => '/js/script.js',
		'integrity' => 'sha256-abc123',
		'crossorigin' => true,
	]), $asset->getImportElement());
});


test('getHtmlPreloadElement()', function () {
	$asset = new ScriptAsset('/js/script.js');

	Assert::equal(Html::el('link', [
		'rel' => 'preload',
		'href' => '/js/script.js',
		'as' => 'script',
	]), $asset->getPreloadElement());

	// Test modulepreload
	$moduleAsset = new ScriptAsset('/js/module.js', type: 'module');

	Assert::equal(Html::el('link', [
		'rel' => 'modulepreload',
		'href' => '/js/module.js',
	]), $moduleAsset->getPreloadElement());
});
