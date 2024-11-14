<?php

declare(strict_types=1);

use Nette\Assets\Asset;
use Nette\Assets\Mapper;
use Nette\Assets\Registry;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class CustomMapper implements Mapper
{
	public function getAsset(string $reference, array $options = []): Asset
	{
		throw new Exception;
	}
}


test('custom mapper as string', function () {
	$container = createContainer('
	assets:
		mapping:
			default: \CustomMapper
	');

	$registy = $container->getByType(Registry::class);
	$mapper = $registy->getMapper();
	Assert::type(CustomMapper::class, $mapper);
});
