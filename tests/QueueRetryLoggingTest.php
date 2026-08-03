<?php

declare(strict_types=1);

namespace AppLogger\Tests;

use AppLogger\LoggerDecorator;
use AppLogger\Queue\FailedJobContextNormalizer;
use AppLogger\Queue\QueuedJobResolver;
use AppLogger\Queue\QueueExceptionReportTracker;
use AppLogger\Queue\QueueExecutionContext;
use AppLogger\Queue\RetryAttemptLogger;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Psr\Log\LoggerInterface;
use RuntimeException;

test("Направляет предупреждение в индекс warning", function(): void {
	$logger = $this->createMock(LoggerInterface::class);
	$logger->expects($this->once())
		->method("log")
		->with("warning", "Повторная попытка", ["index_name" => "warning"]);

	(new LoggerDecorator($logger))->warning("Повторная попытка");
});

test("Понижает ошибку активной попытки до warning", function(): void {
	$context = new QueueExecutionContext();
	$context->start();
	$logger = $this->createMock(LoggerInterface::class);
	$logger->expects($this->once())
		->method("log")
		->with("warning", "Временная ошибка", ["index_name" => "warning"]);

	(new LoggerDecorator($logger, $context))->error("Временная ошибка");
});

test("Логирует исключение промежуточной попытки", function(): void {
	$exception = new RuntimeException("temporary failure");
	$job = $this->createMock(Job::class);
	$job->method("payload")->willReturn([
		"data" => ["command" => serialize((object)["task_id" => 77])],
	]);
	$job->method("resolveName")->willReturn("ExampleJob");
	$job->method("uuid")->willReturn("job-uuid");
	$job->method("getQueue")->willReturn("default");
	$job->method("attempts")->willReturn(2);
	$job->method("maxTries")->willReturn(3);
	$job->method("hasFailed")->willReturn(false);
	$logger = $this->createMock(LoggerInterface::class);
	$logger->expects($this->once())
		->method("warning")
		->with(
			"Задание очереди будет выполнено повторно.",
			$this->callback(fn(array $context): bool => $context["attempts"] === 2
				&& $context["max_attempts"] === 3
				&& $context["error"] === "temporary failure"
				&& $context["index_name"] === "warning"),
		);
	$tracker = new QueueExceptionReportTracker();
	$listener = new RetryAttemptLogger(
		$logger,
		new QueuedJobResolver(),
		new FailedJobContextNormalizer(8192, 32768),
		$tracker,
		"warning",
		true,
	);

	$listener->handle(new JobExceptionOccurred("database", $job, $exception));

	expect($tracker->consume($exception))->toBeTrue();
});
