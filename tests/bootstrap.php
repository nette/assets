<?php

declare(strict_types=1);

// The Nette Tester command-line runner can be
// invoked through the command: ../vendor/bin/tester .

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer install`';
	exit(1);
}


// configure environment
Tester\Environment::setup();
Tester\Environment::setupFunctions();
Mockery::setLoader(new Mockery\Loader\RequireLoader(getTempDir()));


function getTempDir(): string
{
	$dir = __DIR__ . '/tmp/' . getmypid();

	if (empty($GLOBALS['\lock'])) {
		// garbage collector
		$GLOBALS['\lock'] = $lock = fopen(__DIR__ . '/lock', 'w');
		if (rand(0, 100)) {
			flock($lock, LOCK_SH);
			@mkdir(dirname($dir));
		} elseif (flock($lock, LOCK_EX)) {
			Tester\Helpers::purge(dirname($dir));
		}

		@mkdir($dir);
	}

	return $dir;
}


function createContainer(string $config): Nette\DI\Container
{
	$compiler = new Nette\DI\Compiler;
	$compiler->loadConfig(Tester\FileMock::create($config, 'neon'));
	$compiler->addExtension('assets', new Nette\Bridges\Assets\DIExtension);
	$builder = $compiler->getContainerBuilder();

	static $counter;
	$class = 'Container' . ($counter++);
	$code = $compiler->setClassName($class)->compile();
	eval($code);
	return new $class;
}
