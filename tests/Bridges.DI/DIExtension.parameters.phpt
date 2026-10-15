<?php declare(strict_types=1);

use Nette\Assets\{FilesystemMapper, Registry};
use Nette\Bridges\AssetsDI\DIExtension;
use Nette\{DI, Http};
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$compiler = new DI\Compiler;
$compiler->loadConfig(Tester\FileMock::create('
	assets:
		mapping:
			default: foo
	', 'neon'));

$compiler->addExtension('assets', new DIExtension('http://example.com/bar/', '/www'));

$builder = $compiler->getContainerBuilder();
$builder->addImportedDefinition('httpRequest')->setType(Http\IRequest::class);

$class = 'Container';
$code = $compiler->setClassName($class)->compile();
eval($code);
$container = new $class;

$registy = $container->getByType(Registry::class);

$mapper = $registy->getMapper();
Assert::type(FilesystemMapper::class, $mapper);

// Assert the resolved base URL:
// Expected: base URL ('/bar/') + '/' + mapper relative path ('foo')
Assert::same('http://example.com/bar/foo', $mapper->getBaseUrl());

// Assert the resolved base path:
// Expected: base path ('/www') + '/' + mapper relative path ('foo')
$sep = DIRECTORY_SEPARATOR;
Assert::same("{$sep}www{$sep}foo", $mapper->getBasePath());
