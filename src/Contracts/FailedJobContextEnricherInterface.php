<?php

declare(strict_types=1);

namespace AppLogger\Contracts;

use Throwable;

/**
 * Контракт дополнительного обогащения контекста упавшего задания.
 */
interface FailedJobContextEnricherInterface {
	/**
	 * Получить дополнительный контекст задания.
	 *
	 * @param string $jobClass Класс задания.
	 * @param object|null $job Объект задания, если его удалось восстановить.
	 * @param Throwable $exception Исключение задания.
	 * @param array<string, mixed> $context Базовый контекст задания.
	 * @return array<string, mixed>
	 */
	public function enrich(string $jobClass, ?object $job, Throwable $exception, array $context): array;
}
