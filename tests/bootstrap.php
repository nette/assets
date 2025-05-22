<?php

declare(strict_types=1);

use Tester\Assert;

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
	$compiler->addExtension('assets', new Nette\Bridges\AssetsDI\DIExtension);
	$builder = $compiler->getContainerBuilder();

	static $counter;
	$class = 'Container' . ($counter++);
	$code = $compiler->setClassName($class)->compile();
	eval($code);
	return new $class;
}


function assertAssets(array $expected, array $actual): void
{
	Assert::same(array_keys($expected), array_keys($actual));
	foreach ($expected as $i => $exp) {
		Assert::type($exp::class, $actual[$i]);
		foreach ($exp as $prop => $value) {
			Assert::same($value, $actual[$i]->$prop, "property $$prop");
		}
	}
}
