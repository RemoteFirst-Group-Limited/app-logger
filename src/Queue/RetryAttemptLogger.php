<?php

declare(strict_types=1);

namespace AppLogger\Queue;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Логирует исключения промежуточных попыток заданий очереди.
 */
class RetryAttemptLogger {
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly QueuedJobResolver $jobResolver,
		private readonly FailedJobContextNormalizer $normalizer,
		private readonly QueueExceptionReportTracker $reportTracker,
		private readonly string $indexName,
		private readonly bool $failedJobLoggingEnabled,
	) {
	}

	/**
	 * Обработать исключение попытки задания очереди.
	 */
	public function handle(JobExceptionOccurred $event): void {
		if ($event->job->hasFailed()) {
			$this->rememberFinalException($event->exception);
			return;
		}

		$this->reportTracker->remember($event->exception);
		try {
			$this->logger->warning(
				"Задание очереди будет выполнено повторно.",
				$this->context($event),
			);
		} catch (Throwable) {
			// Ошибка диагностического логирования не должна влиять на очередь.
		}
	}

	/**
	 * Собрать диагностический контекст повторной попытки.
	 *
	 * @return array<string, mixed>
	 */
	private function context(JobExceptionOccurred $event): array {
		$systemContext = $this->systemContext($event);
		$jobContext = array_diff_key($this->jobContext($event->job), $systemContext);
		$context = $this->normalizer->normalizeContext([...$systemContext, ...$jobContext]);
		$context["index_name"] = $this->indexName;

		return $context;
	}

	/**
	 * Собрать обязательный системный контекст.
	 *
	 * @return array<string, mixed>
	 */
	private function systemContext(JobExceptionOccurred $event): array {
		return [
			"job" => $event->job->resolveName(),
			"job_uuid" => $event->job->uuid(),
			"connection" => $event->connectionName,
			"queue" => $event->job->getQueue(),
			"attempts" => $event->job->attempts(),
			"max_attempts" => $event->job->maxTries(),
			"error" => $this->normalizer->normalizeText($event->exception->getMessage()),
			"exception" => $event->exception::class,
		];
	}

	/**
	 * Собрать безопасный контекст объекта задания.
	 *
	 * @return array<string, mixed>
	 */
	private function jobContext(Job $job): array {
		$command = $this->jobResolver->resolve($job->payload());

		return $command === null ? [] : $this->normalizer->normalizeJob($command);
	}

	/**
	 * Запомнить финальное исключение при включённом failed-job логировании.
	 */
	private function rememberFinalException(Throwable $exception): void {
		if ($this->failedJobLoggingEnabled) {
			$this->reportTracker->remember($exception);
		}
	}
}
