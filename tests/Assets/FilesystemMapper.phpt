<?php

declare(strict_types=1);

use Nette\Assets\FilesystemMapper;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


touch(__DIR__ . '/fixtures/test.txt', 2_700_000_000);

test('Basic mapper functionality', function () {
	$mapper = new FilesystemMapper('http://example.com/assets', __DIR__ . '/fixtures');
	$asset = $mapper->getAsset('test.txt');

	Assert::same('http://example.com/assets/test.txt?v=2700000000', $asset->getUrl());
	Assert::same(__DIR__ . '/fixtures/test.txt', $asset->getPath());
	Assert::true($asset->exists());
});

test('URL without trailing slash', function () {
	$mapper = new FilesystemMapper('http://example.com/assets/', __DIR__ . '/fixtures/');
	$asset = $mapper->getAsset('test.txt');

	Assert::same('http://example.com/assets/test.txt?v=2700000000', $asset->getUrl());
});

test('Non-existent file version handling', function () {
	$mapper = new FilesystemMapper('http://example.com/assets', __DIR__ . '/fixtures');
	$asset = $mapper->getAsset('missing.txt');

	Assert::same('http://example.com/assets/missing.txt', $asset->getUrl());
	Assert::false($asset->exists());
});

test('Mandatory extension autodetection', function () {
	$mapper = new FilesystemMapper(
		'http://example.com/assets',
		__DIR__ . '/fixtures',
		['gif', 'jpg'],
	);

	$exact = $mapper->getAsset('image.gif');
	Assert::match('http://example.com/assets/image.gif?v=%d%', $exact->getUrl());
	Assert::true($exact->exists());

	$gif = $mapper->getAsset('image');
	Assert::match('http://example.com/assets/image.gif?v=%d%', $gif->getUrl());
	Assert::true($gif->exists());

	$notFound = $mapper->getAsset('missing');
	Assert::same('http://example.com/assets/missing.gif', $notFound->getUrl());
	Assert::false($notFound->exists());

	$subdir = $mapper->getAsset('subdir');
	Assert::same('http://example.com/assets/subdir.gif', $subdir->getUrl());
	Assert::false($subdir->exists());
});

test('Optional extension autodetection', function () {
	$mapper = new FilesystemMapper(
		'http://example.com/assets',
		__DIR__ . '/fixtures',
		['gif', 'jpg', ''],
	);

	$gif = $mapper->getAsset('image');
	Assert::match('http://example.com/assets/image.gif?v=%d%', $gif->getUrl());
	Assert::true($gif->exists());

	$notFound = $mapper->getAsset('missing');
	Assert::same('http://example.com/assets/missing', $notFound->getUrl());
	Assert::false($notFound->exists());
});

test('Option validation', function () {
	$mapper = new FilesystemMapper('http://example.com/assets', __DIR__ . '/fixtures');

	Assert::exception(
		fn() => $mapper->getAsset('test.txt', ['invalid' => true]),
		InvalidArgumentException::class,
		'Unsupported asset options: invalid',
	);
});

test('resolveUrl()', function () {
	$mapper = new FilesystemMapper('http://example.com/assets', __DIR__ . '/fixtures');

	Assert::same('http://example.com/assets/test.txt', $mapper->resolveUrl('test.txt'));
	Assert::same('http://example.com/assets/subdir/', $mapper->resolveUrl('subdir/'));
	Assert::same('http://example.com/assets/subdir/file.jpg', $mapper->resolveUrl('subdir/file.jpg'));
});

test('resolvePath()', function () {
	$mapper = new FilesystemMapper('http://example.com/assets', __DIR__ . '/fixtures');

	Assert::same(__DIR__ . '/fixtures/test.txt', $mapper->resolvePath('test.txt'));
	Assert::same(__DIR__ . '/fixtures/subdir/', $mapper->resolvePath('subdir/'));
	Assert::same(__DIR__ . '/fixtures/subdir/file.jpg', $mapper->resolvePath('subdir/file.jpg'));
});