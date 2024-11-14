<?php

declare(strict_types=1);

use Nette\Assets\FilesystemMapper;
use Nette\Assets\Registry;
use Nette\Bridges\Assets\DIExtension;
use Nette\DI;
use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$compiler = new DI\Compiler;
$compiler->loadConfig(Tester\FileMock::create('
	parameters:
		# Define wwwDir parameter, which the extension uses as the default base path if assets.path is missing
		wwwDir: /www

	assets:
		mapping:
			default: foo
	', 'neon'));

$compiler->addExtension('assets', new DIExtension);

$builder = $compiler->getContainerBuilder();
$builder->addImportedDefinition('httpRequest')->setType(Http\IRequest::class);

$class = 'Container';
$code = $compiler->setClassName($class)->compile();
eval($code);
$container = new $class;

$httpRequest = Mockery::mock(Http\IRequest::class);
$httpRequest->shouldReceive('getUrl')->andReturn(new Http\UrlScript('http://example.com/bar/index.php', '/bar/'));
$container->addService('httpRequest', $httpRequest);

$registy = $container->getByType(Registry::class);

$mapper = $registy->getMapper();
Assert::type(FilesystemMapper::class, $mapper);

// Assert the resolved base URL:
// Expected: Autodetected base URL ('/bar/') + '/' + mapper relative path ('foo') + '/'
// The trailing slash is added by resolveUrl('') when checking the base.
Assert::same('http://example.com/bar/foo/', $mapper->resolveUrl(''));

// Assert the resolved base path:
// Expected: Autodetected base path (%wwwDir% = '/www') + '/' + mapper relative path ('foo') + '/'
// The trailing slash is added by resolvePath('') when checking the base.
$S = DIRECTORY_SEPARATOR;
Assert::same("{$S}www{$S}foo/", $mapper->resolvePath(''));
