<?php declare(strict_types=1);

use Nette\Assets\{EntryAsset, Registry, ViteMapper};
use Nette\Bridges\AssetsDI\DIExtension;
use Nette\DI\{Compiler, Container, ContainerLoader};
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$loader = new ContainerLoader(getTempDir(), autoRebuild: true);
$key = __FILE__;

$class = $loader->load(function (Compiler $compiler): void {
	$basePath = __DIR__ . '/../Assets/fixtures';
	$compiler->addExtension('assets', new DIExtension(debugMode: true));
	$compiler->addConfig([
		'assets' => [
			'baseUrl' => 'https://example.com/foo',
			'basePath' => $basePath,
			'mapping' => [
				'vite-dev' => [
					'type' => 'vite',
					'url' => '/dist',
					'path' => $basePath,
					'manifest' => 'manifest.json',
					'devServer' => 'http://localhost:5173',
				],

			],
		],
	]);
}, $key);

$container = new $class;
Assert::type(Container::class, $container);


test('ViteMapper dev configuration', function () use ($container): void {
	$registry = $container->getByType(Registry::class);
	Assert::type(Registry::class, $registry);

	$viteMapper = $registry->getMapper('vite-dev');
	Assert::type(ViteMapper::class, $viteMapper);

	$asset = $registry->getAsset('vite-dev:src/main.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('http://localhost:5173/src/main.js', $asset->url);

	assertAssets([
		new Nette\Assets\ScriptAsset('http://localhost:5173/@vite/client', type: 'module'),
	], $asset->imports);
	assertAssets([
	], $asset->preloads);
});
