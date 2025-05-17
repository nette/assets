<?php

declare(strict_types=1);

use Nette\Assets\FilesystemMapper;
use Nette\Assets\Registry;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('Global path and URL settings with relative mapper string', function () {
	$container = createContainer('
	assets:
		basePath: /data/static   # Explicit global path
		baseUrl: /static-assets  # Explicit global URL prefix
		mapping:
			default: theme1  # "theme1" is relative to global settings
	');

	$registy = $container->getByType(Registry::class);
	$mapper = $registy->getMapper();

	$S = DIRECTORY_SEPARATOR;
	Assert::type(FilesystemMapper::class, $mapper);
	Assert::same('/static-assets/theme1', $mapper->getBaseUrl());
	Assert::same("{$S}data{$S}static{$S}theme1", $mapper->getBasePath());
});


test('Global settings with absolute mapper structure', function () {
	$container = createContainer('
	assets:
		basePath: /data/static
		baseUrl: /static-assets
		mapping:
			images:
				path: /img
				url: /img-cdn

			cdn:
				path: compiled/css
				url: https://cdn.example.com/styles/

			empty:
	');

	$registy = $container->getByType(Registry::class);
	$S = DIRECTORY_SEPARATOR;

	$mapper = $registy->getMapper('images');
	Assert::type(FilesystemMapper::class, $mapper);
	Assert::same('/img-cdn', $mapper->getBaseUrl());
	Assert::same("{$S}img", $mapper->getBasePath());

	$mapper = $registy->getMapper('cdn');
	Assert::type(FilesystemMapper::class, $mapper);
	Assert::same('https://cdn.example.com/styles', $mapper->getBaseUrl());
	Assert::same("{$S}data{$S}static{$S}compiled{$S}css", $mapper->getBasePath());

	$mapper = $registy->getMapper('empty');
	Assert::type(FilesystemMapper::class, $mapper);
	Assert::same('/static-assets', $mapper->getBaseUrl());
	Assert::same('/data/static', $mapper->getBasePath());
});


test('No configuration', function () {
	$container = createContainer('
	assets:
		basePath: /data/
		baseUrl: /data/
	');

	$registy = $container->getByType(Registry::class);
	$S = DIRECTORY_SEPARATOR;

	$mapper = $registy->getMapper(Registry::DefaultScope);
	Assert::type(FilesystemMapper::class, $mapper);
	Assert::same('/data/assets', $mapper->getBaseUrl());
	Assert::same("{$S}data{$S}assets", $mapper->getBasePath());
});
