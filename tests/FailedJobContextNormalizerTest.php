<?php

declare(strict_types=1);

namespace AppLogger\Tests;

use AppLogger\Queue\FailedJobContextNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет безопасную нормализацию контекста задания.
 */
final class FailedJobContextNormalizerTest extends TestCase {
	/**
	 * Проверить извлечение только безопасных значений.
	 */
	public function testNormalizesOnlySafeJobProperties(): void {
		$job = new class {
			private int $taskId = 15;
			private ?string $optionalValue = null;
			private object $model;

			public function __construct() {
				$this->model = (object)["secret" => "value"];
			}
		};
		$normalizer = new FailedJobContextNormalizer(8192, 32768);

		$context = $normalizer->normalizeJob($job);

		self::assertSame(15, $context["task_id"]);
		self::assertArrayNotHasKey("optional_value", $context);
		self::assertArrayNotHasKey("model", $context);
	}

	/**
	 * Проверить ограничение поля payload.
	 */
	public function testLimitsPayload(): void {
		$job = new class {
			/** @var array<string, string> */
			private array $payload = [];

			public function __construct() {
				$this->payload = ["content" => str_repeat("Файл", 5000)];
			}
		};
		$normalizer = new FailedJobContextNormalizer(8192, 32768);

		$payload = $normalizer->normalizeJob($job)["payload"];

		self::assertTrue($payload["truncated"]);
		self::assertLessThanOrEqual(8192, strlen((string)json_encode($payload, JSON_UNESCAPED_UNICODE)));
	}
}
