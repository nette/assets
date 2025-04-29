<?php

declare(strict_types=1);

use Nette\Assets\EntryAsset;
use Nette\Assets\GenericAsset;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// Test basic functionality of EntryAsset
test('EntryAsset basic functionality', function (): void {
	$url = 'https://example.com/assets/main.js';
	$dependencies = [
		new GenericAsset('https://example.com/assets/chunk1.js'),
		new GenericAsset('https://example.com/assets/styles.css'),
	];
	$path = '/var/www/html/assets/main.js';

	$asset = new EntryAsset($url, 'application/javascript', $dependencies, $path);

	// Check basic properties
	Assert::same($url, $asset->url);
	Assert::same($url, (string) $asset);
	Assert::same($dependencies, $asset->dependencies);
	Assert::same($path, $asset->sourcePath);
	Assert::same('application/javascript', $asset->mimeType);
});

// Test file type detection
test('EntryAsset file type detection', function (): void {
	// JavaScript detection
	$jsAsset = new EntryAsset('https://example.com/main.js', 'application/javascript');
	Assert::true(str_ends_with(strtolower($jsAsset->url), '.js'));

	$mjsAsset = new EntryAsset('https://example.com/main.mjs', 'application/javascript');
	Assert::true(str_ends_with(strtolower($mjsAsset->url), '.mjs'));

	// CSS detection
	$cssAsset = new EntryAsset('https://example.com/styles.css', 'text/css');
	Assert::true(str_ends_with(strtolower($cssAsset->url), '.css'));
});
