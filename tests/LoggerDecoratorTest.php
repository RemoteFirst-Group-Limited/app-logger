<?php

declare(strict_types=1);

namespace AppLogger\Tests;

use AppLogger\LoggerDecorator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Проверяет базовый контракт существующего декоратора.
 */
final class LoggerDecoratorTest extends TestCase {
	/**
	 * Проверить добавление error-индекса.
	 */
	public function testAddsErrorIndex(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects(self::once())
			->method("log")
			->with("error", "Ошибка", [
				"job" => "ExampleJob",
				"index_name" => "error",
			]);

		(new LoggerDecorator($logger))->error("Ошибка", ["job" => "ExampleJob"]);
	}
}
