<?php

declare(strict_types=1);

namespace AppLogger\Queue;

use AppLogger\Contracts\FailedJobContextEnricherInterface;
use Illuminate\Queue\Events\JobFailed;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Записывает диагностическую информацию об окончательно упавшем задании.
 */
class FailedJobLogger {
	/**
	 * @param array<int, FailedJobContextEnricherInterface> $enrichers Дополнительные обогатители.
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly QueuedJobResolver $jobResolver,
		private readonly FailedJobContextNormalizer $normalizer,
		private readonly string $indexName,
		private readonly array $enrichers = [],
	) {
	}

	/**
	 * Обработать событие окончательного провала задания.
	 */
	public function handle(JobFailed $event): void {
		try {
			$jobClass = $event->job->resolveName();
			$systemContext = $this->systemContext($event, $jobClass);
			$jobContext = array_diff_key($this->jobContext($event, $jobClass), $systemContext);
			$context = $this->normalizer->normalizeContext([...$systemContext, ...$jobContext]);
			$context["index_name"] = $this->indexName;
			$this->logger->error(
				"Задание очереди завершилось с ошибкой.",
				$context,
			);
		} catch (Throwable) {
			// Ошибка диагностического логирования не должна влиять на очередь.
		}
	}

	/**
	 * Собрать контекст объекта задания.
	 *
	 * @return array<string, mixed>
	 */
	private function jobContext(JobFailed $event, string $jobClass): array {
		$job = $this->jobResolver->resolve($event->job->payload());
		$context = $job === null ? [] : $this->normalizer->normalizeJob($job);
		foreach ($this->enrichers as $enricher) {
			try {
				$context = [
					...$context,
					...$enricher->enrich($jobClass, $job, $event->exception, $context),
				];
			} catch (Throwable) {
				continue;
			}
		}

		return $this->normalizer->normalizeContext($context);
	}

	/**
	 * Собрать обязательный системный контекст.
	 *
	 * @return array<string, mixed>
	 */
	private function systemContext(JobFailed $event, string $jobClass): array {
		return [
			"job" => $jobClass,
			"job_uuid" => $event->job->uuid(),
			"connection" => $event->connectionName,
			"queue" => $event->job->getQueue(),
			"attempts" => $event->job->attempts(),
			"error" => $this->normalizer->normalizeText($event->exception->getMessage()),
			"exception" => $event->exception::class,
		];
	}
}
