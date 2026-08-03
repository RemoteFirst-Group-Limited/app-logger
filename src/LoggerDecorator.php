<?php

declare(strict_types=1);

namespace AppLogger;

use AppLogger\Queue\QueueExecutionContext;
use BadMethodCallException;
use Psr\Log\LoggerInterface;
use Stringable;

/**
 * Декоратор логгера с обязательным `index_name` в контексте.
 *
 * @mixin \Psr\Log\LoggerInterface
 * @method self channel(string|null $channel = null)
 * @method self stack(array $channels, string|null $channel = null)
 * @method self driver(string|null $driver = null)
 * @method self build(array $config)
 * @method self withContext(array $context = [])
 * @method self withoutContext()
 * @method mixed listen(\Closure $callback)
 */
class LoggerDecorator implements LoggerInterface {
	/**
	 * Имя индекса для сообщений с уровнем error и выше.
	 */
	private const INDEX_ERROR = "error";

	/**
	 * Имя индекса для предупреждений.
	 */
	private const INDEX_WARNING = "warning";

	/**
	 * Имя индекса для информационных сообщений.
	 */
	private const INDEX_INFO = "info";

	/**
	 * Имя индекса для отладочных сообщений.
	 */
	private const INDEX_DEBUG = "debug";

	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly ?QueueExecutionContext $queueContext = null,
	) {
	}

	/**
	 * Записать сообщение уровня emergency.
	 */
	public function emergency(string|Stringable $message, array $context = []): void {
		$this->write("emergency", $message, self::INDEX_ERROR, $context);
	}

	/**
	 * Записать сообщение уровня alert.
	 */
	public function alert(string|Stringable $message, array $context = []): void {
		$this->write("alert", $message, self::INDEX_ERROR, $context);
	}

	/**
	 * Записать сообщение уровня critical.
	 */
	public function critical(string|Stringable $message, array $context = []): void {
		$this->write("critical", $message, self::INDEX_ERROR, $context);
	}

	/**
	 * Записать сообщение уровня error.
	 */
	public function error(string|Stringable $message, array $context = []): void {
		$this->write("error", $message, self::INDEX_ERROR, $context);
	}

	/**
	 * Записать ticket-сообщение с заданным индексом.
	 *
	 * @param string $indexName Значение поля `index_name`.
	 */
	public function forTicket(string|Stringable $message, string $indexName, array $context = []): void {
		$this->write("error", $message, $indexName, $context);
	}

	/**
	 * Записать сообщение уровня warning.
	 */
	public function warning(string|Stringable $message, array $context = []): void {
		$this->write("warning", $message, self::INDEX_WARNING, $context);
	}

	/**
	 * Записать сообщение уровня notice.
	 */
	public function notice(string|Stringable $message, array $context = []): void {
		$this->write("notice", $message, self::INDEX_INFO, $context);
	}

	/**
	 * Записать сообщение уровня info.
	 */
	public function info(string|Stringable $message, array $context = []): void {
		$this->write("info", $message, self::INDEX_INFO, $context);
	}

	/**
	 * Записать сообщение уровня debug.
	 */
	public function debug(string|Stringable $message, array $context = []): void {
		$this->write("debug", $message, self::INDEX_DEBUG, $context);
	}

	/**
	 * Записать сообщение указанного уровня.
	 */
	public function log($level, string|Stringable $message, array $context = []): void {
		$logLevel = (string)$level;
		$indexName = match ($logLevel) {
			"debug" => self::INDEX_DEBUG,
			"info", "notice" => self::INDEX_INFO,
			"warning" => self::INDEX_WARNING,
			default => self::INDEX_ERROR,
		};

		$this->write($logLevel, $message, $indexName, $context);
	}

	/**
	 * Делегировать неизвестные методы базовому логгеру.
	 */
	public function __call(string $method, array $arguments): mixed {
		if (!is_callable([$this->logger, $method])) {
			throw new BadMethodCallException(sprintf("Method %s::%s is not callable.", $this->logger::class, $method));
		}
		$result = $this->logger->{$method}(...$arguments);
		if ($result === $this->logger) {
			return $this;
		}
		if ($result instanceof LoggerInterface) {
			return new self($result, $this->queueContext);
		}

		return $result;
	}

	/**
	 * Записать сообщение в базовый логгер с установкой `index_name`.
	 *
	 * @param string $level Уровень логирования.
	 * @param string|Stringable $message Текст сообщения.
	 * @param string $indexName Индекс для маршрутизации в ELK.
	 * @param array<string,mixed> $context Контекст сообщения.
	 */
	private function write(string $level, string|Stringable $message, string $indexName, array $context): void {
		[$level, $indexName] = $this->resolveQueueLevel($level, $indexName);
		$context["index_name"] = $indexName;
		$this->logger->log($level, $message, $context);
	}

	/**
	 * Определить уровень и индекс сообщения внутри задания очереди.
	 *
	 * @return array{0: string, 1: string}
	 */
	private function resolveQueueLevel(string $level, string $indexName): array {
		$errorLevels = ["emergency", "alert", "critical", "error"];
		if ($indexName !== self::INDEX_ERROR || !in_array($level, $errorLevels, true)) {
			return [$level, $indexName];
		}
		if (!$this->queueContext?->isActive()) {
			return [$level, $indexName];
		}

		return ["warning", self::INDEX_WARNING];
	}
}
