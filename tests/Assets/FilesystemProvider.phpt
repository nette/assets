<?php

declare(strict_types=1);

use Nette\Assets\FilesystemProvider;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


touch(__DIR__ . '/fixtures/test.txt', 2_700_000_000);

test('Basic provider functionality', function () {
	$provider = new FilesystemProvider('http://example.com/assets', __DIR__ . '/fixtures');
	$asset = $provider->getAsset('test.txt');

	Assert::same('http://example.com/assets/test.txt?v=2700000000', $asset->getUrl());
	Assert::same(__DIR__ . '/fixtures/test.txt', $asset->getSourcePath());
	Assert::true($asset->exists());
});

test('URL without trailing slash', function () {
	$provider = new FilesystemProvider('http://example.com/assets/', __DIR__ . '/fixtures/');
	$asset = $provider->getAsset('test.txt');

	Assert::same('http://example.com/assets/test.txt?v=2700000000', $asset->getUrl());
});

test('Non-existent file version handling', function () {
	$provider = new FilesystemProvider('http://example.com/assets', __DIR__ . '/fixtures');
	$asset = $provider->getAsset('missing.txt');

	Assert::same('http://example.com/assets/missing.txt', $asset->getUrl());
	Assert::false($asset->exists());
});

test('Mandatory extension autodetection', function () {
	$provider = new FilesystemProvider(
		'http://example.com/assets',
		__DIR__ . '/fixtures',
		['gif', 'jpg'],
	);

	$exact = $provider->getAsset('image.gif');
	Assert::match('http://example.com/assets/image.gif?v=%d%', $exact->getUrl());
	Assert::true($exact->exists());

	$gif = $provider->getAsset('image');
	Assert::match('http://example.com/assets/image.gif?v=%d%', $gif->getUrl());
	Assert::true($gif->exists());

	$notFound = $provider->getAsset('missing');
	Assert::same('http://example.com/assets/missing.gif', $notFound->getUrl());
	Assert::false($notFound->exists());

	$subdir = $provider->getAsset('subdir');
	Assert::same('http://example.com/assets/subdir', $subdir->getUrl());
	Assert::false($subdir->exists());
});

test('Optional extension autodetection', function () {
	$provider = new FilesystemProvider(
		'http://example.com/assets',
		__DIR__ . '/fixtures',
		['gif', 'jpg', ''],
	);

	$gif = $provider->getAsset('image');
	Assert::match('http://example.com/assets/image.gif?v=%d%', $gif->getUrl());
	Assert::true($gif->exists());

	$notFound = $provider->getAsset('missing');
	Assert::same('http://example.com/assets/missing', $notFound->getUrl());
	Assert::false($notFound->exists());
});

test('Option validation', function () {
	$provider = new FilesystemProvider('http://example.com/assets', __DIR__ . '/fixtures');

	Assert::exception(
		fn() => $provider->getAsset('test.txt', ['invalid' => true]),
		InvalidArgumentException::class,
		'Unsupported asset options: invalid',
	);
});
