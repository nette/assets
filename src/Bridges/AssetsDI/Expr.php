<?php

declare(strict_types=1);

namespace Nette\Bridges\Assets;

use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\Statement;
use Nette\Utils\Callback;


/** @internal */
final class Expr
{
	public static function from(mixed $value): ExprProxy
	{
		return $value instanceof ExprProxy ? $value : new ExprProxy($value);
	}


	public static function byType(string $type): ExprProxy
	{
		return new ExprProxy(Reference::fromType($type));
	}


	public static function call(string|array|\Closure $function, ...$args): ExprProxy
	{
		if ($function instanceof \Closure) {
			$function = Callback::unwrap($function);
		}
		return self::containsStatements($args)
			? new ExprProxy(new Statement(['', $function], $args))
			: new ExprProxy($function(...$args));
	}


	public static function resolve(mixed $proxy): mixed
	{
		return match (true) {
			is_array($proxy) => array_map(self::resolve(...), $proxy),
			$proxy instanceof ExprProxy => $proxy->unwrap(),
			default => $proxy,
		};
	}


	public static function containsStatements(array &$args): bool
	{
		$res = false;
		foreach ($args as &$item) {
			if ($item instanceof ExprProxy) {
				$item = $item->unwrap();
			}
			$res = $res || $item instanceof Statement;
		}
		return $res;
	}
}


/** @internal */
final class ExprProxy
{
	public function __construct(
		private mixed $value,
	) {
	}


	public function call(string $method, ...$args): self
	{
		return $this->value instanceof Statement || $this->value instanceof Reference || Expr::containsStatements($args)
			? new self(new Statement([$this->value, $method], $args))
			: new self($this->value->$method(...$args));
	}


	public function unwrap(): mixed
	{
		return $this->value;
	}
}
