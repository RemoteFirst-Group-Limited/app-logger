<?php

declare(strict_types=1);

namespace AppLogger\Tests;

use AppLogger\Queue\FailedJobContextNormalizer;
use AppLogger\Queue\FailedJobLogger;
use AppLogger\Queue\QueuedJobResolver;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Проверяет обязательный контекст лога упавшего задания.
 */
final class FailedJobLoggerTest extends TestCase {
	/**
	 * Проверить сохранение индекса после ограничения контекста.
	 */
	public function testPreservesIndexNameAfterContextLimit(): void {
		$queueJob = $this->queueJob();
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects(self::once())
			->method("error")
			->with(
				"Задание очереди завершилось с ошибкой.",
				self::callback(fn(array $context): bool => $context["index_name"] === "error"),
			);
		$listener = new FailedJobLogger(
			$logger,
			new QueuedJobResolver(),
			new FailedJobContextNormalizer(8192, 2048),
			"error",
		);

		$listener->handle(new JobFailed("database", $queueJob, new RuntimeException("boom")));
	}

	/**
	 * Создать задание очереди с контекстом на границе лимита.
	 */
	private function queueJob(): Job {
		$job = $this->createMock(Job::class);
		$job->method("payload")->willReturn([
			"data" => ["command" => serialize(new FailedJobFixture())],
		]);
		$job->method("resolveName")->willReturn(str_repeat("J", 1900));
		$job->method("uuid")->willReturn("job-uuid");
		$job->method("getQueue")->willReturn("default");
		$job->method("attempts")->willReturn(3);

		return $job;
	}
}

/**
 * Тестовое задание без прикладного контекста.
 */
final class FailedJobFixture {
}
