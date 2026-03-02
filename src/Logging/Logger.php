<?php

declare(strict_types=1);

namespace AppLogger\Logging;

use BadMethodCallException;
use Psr\Log\LoggerInterface;
use Stringable;

/**
 * @mixin \Psr\Log\LoggerInterface
 *
 * @method self channel(string|null $channel = null)
 * @method self stack(array $channels, string|null $channel = null)
 * @method self driver(string|null $driver = null)
 * @method self build(array $config)
 * @method self withContext(array $context = [])
 * @method self withoutContext()
 * @method mixed listen(\Closure $callback)
 */
final class Logger implements LoggerInterface
{
    /**
     * Имя индекса для сообщений с уровнем error и выше.
     */
    private const INDEX_ERROR = 'error';

    /**
     * Имя индекса для информационных сообщений.
     */
    private const INDEX_INFO = 'info';

    /**
     * Имя индекса для отладочных сообщений.
     */
    private const INDEX_DEBUG = 'debug';

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * Записывает сообщение уровня emergency.
     */
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->write('emergency', $message, self::INDEX_ERROR, $context);
    }

    /**
     * Записывает сообщение уровня alert.
     */
    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->write('alert', $message, self::INDEX_ERROR, $context);
    }

    /**
     * Записывает сообщение уровня critical.
     */
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->write('critical', $message, self::INDEX_ERROR, $context);
    }

    /**
     * Записывает сообщение уровня error.
     */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->write('error', $message, self::INDEX_ERROR, $context);
    }

    /**
     * Записывает сообщение уровня warning.
     */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->write('warning', $message, self::INDEX_ERROR, $context);
    }

    /**
     * Записывает сообщение уровня notice.
     */
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->write('notice', $message, self::INDEX_ERROR, $context);
    }

    /**
     * Записывает сообщение уровня info.
     */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->write('info', $message, self::INDEX_INFO, $context);
    }

    /**
     * Записывает сообщение уровня debug.
     */
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->write('debug', $message, self::INDEX_DEBUG, $context);
    }

    /**
     * Записывает сообщение указанного уровня.
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->write((string) $level, $message, self::INDEX_ERROR, $context);
    }

    /**
     * Делегирует неизвестные методы реальному Laravel logger/manager.
     *
     * Если метод возвращает логгер, оборачивает его в AppLogger\Logging\Logger
     * чтобы fluent-цепочки продолжали добавлять index_name.
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (!is_callable([$this->logger, $method])) {
            throw new BadMethodCallException(sprintf('Method %s::%s is not callable.', $this->logger::class, $method));
        }

        $result = $this->logger->{$method}(...$arguments);

        if ($result === $this->logger) {
            return $this;
        }

        if ($result instanceof LoggerInterface) {
            return new self($result);
        }

        return $result;
    }

    /**
     * Делегирует запись в базовый логгер с обязательной установкой index_name.
     */
    private function write(string $level, string|Stringable $message, string $indexName, array $context): void
    {
        $context['index_name'] = $indexName;

        $this->logger->log($level, $message, $context);
    }
}
