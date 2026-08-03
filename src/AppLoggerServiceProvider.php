<?php

declare(strict_types=1);

namespace AppLogger;

use AppLogger\Contracts\FailedJobContextEnricherInterface;
use AppLogger\Queue\FailedJobContextNormalizer;
use AppLogger\Queue\FailedJobLogger;
use AppLogger\Queue\QueuedJobResolver;
use AppLogger\Queue\QueueExceptionReportTracker;
use AppLogger\Queue\QueueExecutionContext;
use AppLogger\Queue\RetryAttemptLogger;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\LogManager;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Регистрирует общий логгер приложения и обработчик упавших заданий.
 */
class AppLoggerServiceProvider extends ServiceProvider {
	/**
	 * Зарегистрировать сервисы пакета.
	 */
	public function register(): void {
		$this->mergeConfigFrom(__DIR__ . "/../config/app-logger.php", "app-logger");
		$this->registerQueueStateServices();
		if ($this->shouldDecorateLogger()) {
			$this->registerLogger();
		}
		$this->registerFailedJobServices();
		$this->registerRetryJobServices();
	}

	/**
	 * Подписаться на события очереди.
	 */
	public function boot(Dispatcher $events, ExceptionHandler $exceptionHandler): void {
		if ($this->retryLoggingEnabled()) {
			$this->registerRetryListeners($events);
			$this->registerExceptionSuppression($exceptionHandler);
		}

		if ($this->failedJobLoggingEnabled()) {
			$events->listen(JobFailed::class, [FailedJobLogger::class, "handle"]);
		}
	}

	/**
	 * Зарегистрировать декоратор логгера.
	 */
	private function registerLogger(): void {
		Facade::clearResolvedInstance("log");
		$this->app->extend("log", function($logManager, $app): LoggerDecorator {
			if ($logManager instanceof LogManager) {
				$app->instance(LogManager::class, $logManager);
			}

			return new LoggerDecorator($logManager, $app->make(QueueExecutionContext::class));
		});
		$this->app->bind(LoggerInterface::class, fn($app): LoggerInterface => $app->make("log"));
		$this->app->bind(LoggerDecorator::class, fn($app): LoggerDecorator => $app->make("log"));
	}

	/**
	 * Зарегистрировать сервисы failed-job логирования.
	 */
	private function registerFailedJobServices(): void {
		$this->app->singleton(FailedJobContextNormalizer::class, function($app): FailedJobContextNormalizer {
			$config = $app->make(ConfigRepository::class);
			$payloadLimit = (int)$config->get("app-logger.failed_jobs.payload_limit_bytes", 8192);
			$contextLimit = (int)$config->get("app-logger.failed_jobs.context_limit_bytes", 32768);

			return new FailedJobContextNormalizer(
				max(512, $payloadLimit),
				max(2048, $contextLimit),
			);
		});
		$this->app->singleton(QueuedJobResolver::class, function($app): QueuedJobResolver {
			$encrypter = $app->bound(Encrypter::class) ? $app->make(Encrypter::class) : null;

			return new QueuedJobResolver($encrypter);
		});
		$this->app->singleton(FailedJobLogger::class, fn($app): FailedJobLogger => new FailedJobLogger(
			$this->resolveLogger($app),
			$app->make(QueuedJobResolver::class),
			$app->make(FailedJobContextNormalizer::class),
			(string)$app->make(ConfigRepository::class)->get("app-logger.failed_jobs.index_name", "error"),
			$this->resolveEnrichers($app),
		));
	}

	/**
	 * Зарегистрировать состояние выполнения очереди.
	 */
	private function registerQueueStateServices(): void {
		$this->app->singleton(QueueExecutionContext::class);
		$this->app->singleton(QueueExceptionReportTracker::class);
	}

	/**
	 * Зарегистрировать сервис логирования повторных попыток.
	 */
	private function registerRetryJobServices(): void {
		$this->app->singleton(RetryAttemptLogger::class, fn($app): RetryAttemptLogger => new RetryAttemptLogger(
			$this->resolveLogger($app),
			$app->make(QueuedJobResolver::class),
			$app->make(FailedJobContextNormalizer::class),
			$app->make(QueueExceptionReportTracker::class),
			(string)$app->make(ConfigRepository::class)->get("app-logger.retry_jobs.index_name", "warning"),
			(bool)$app->make(ConfigRepository::class)->get("app-logger.failed_jobs.enabled", true),
		));
	}

	/**
	 * Подписаться на события жизненного цикла попытки.
	 */
	private function registerRetryListeners(Dispatcher $events): void {
		$context = $this->app->make(QueueExecutionContext::class);
		$events->listen(JobProcessing::class, [$context, "start"]);
		$events->listen(JobProcessed::class, [$context, "finish"]);
		$events->listen(JobExceptionOccurred::class, [RetryAttemptLogger::class, "handle"]);
		$events->listen(JobExceptionOccurred::class, [$context, "finish"]);
		$events->listen(JobFailed::class, [$context, "finish"]);
	}

	/**
	 * Отключить стандартный повторный репорт обработанных исключений.
	 */
	private function registerExceptionSuppression(ExceptionHandler $handler): void {
		if (!is_callable([$handler, "dontReportWhen"])) {
			return;
		}

		$tracker = $this->app->make(QueueExceptionReportTracker::class);
		$handler->dontReportWhen(
			fn(Throwable $exception): bool => $tracker->consume($exception),
		);
	}

	/**
	 * Проверить необходимость декорирования общего логгера.
	 */
	private function shouldDecorateLogger(): bool {
		return (bool)$this->app
			->make(ConfigRepository::class)
			->get("app-logger.logger.decorate", true);
	}

	/**
	 * Проверить, включено ли логирование повторных попыток.
	 */
	private function retryLoggingEnabled(): bool {
		return (bool)$this->app
			->make(ConfigRepository::class)
			->get("app-logger.retry_jobs.enabled", true);
	}

	/**
	 * Проверить, включено ли логирование окончательно упавших заданий.
	 */
	private function failedJobLoggingEnabled(): bool {
		return (bool)$this->app
			->make(ConfigRepository::class)
			->get("app-logger.failed_jobs.enabled", true);
	}

	/**
	 * Получить логгер приложения независимо от настройки декоратора.
	 */
	private function resolveLogger($app): LoggerInterface {
		$logger = $app->make("log");
		if (!$logger instanceof LoggerInterface) {
			throw new \LogicException("Laravel log service must implement LoggerInterface.");
		}

		return $logger;
	}

	/**
	 * Получить настроенные обогатители контекста.
	 *
	 * @return array<int, FailedJobContextEnricherInterface>
	 */
	private function resolveEnrichers($app): array {
		$classes = $app->make(ConfigRepository::class)->get("app-logger.failed_jobs.context_enrichers", []);
		if (!is_array($classes)) {
			return [];
		}

		return array_values(array_filter(
			array_map(fn(string $class): mixed => $app->make($class), $classes),
			fn(mixed $enricher): bool => $enricher instanceof FailedJobContextEnricherInterface,
		));
	}
}
