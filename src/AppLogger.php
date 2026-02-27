<?php

declare(strict_types=1);

namespace AppLogger;

use Psr\Log\LoggerInterface;
use Stringable;

final class AppLogger
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * Записывает сообщение уровня emergency.
     */
    public function emergency(string|Stringable $message, string $indexName = 'error', array $context = []): void
    {
        $this->write('emergency', $message, $indexName, $context);
    }

    /**
     * Записывает сообщение уровня alert.
     */
    public function alert(string|Stringable $message, string $indexName = 'error', array $context = []): void
    {
        $this->write('alert', $message, $indexName, $context);
    }

    /**
     * Записывает сообщение уровня critical.
     */
    public function critical(string|Stringable $message, string $indexName = 'error', array $context = []): void
    {
        $this->write('critical', $message, $indexName, $context);
    }

    /**
     * Записывает сообщение уровня error.
     */
    public function error(string|Stringable $message, string $indexName = 'error', array $context = []): void
    {
        $this->write('error', $message, $indexName, $context);
    }

    /**
     * Записывает сообщение уровня warning.
     */
    public function warning(string|Stringable $message, string $indexName = 'error', array $context = []): void
    {
        $this->write('warning', $message, $indexName, $context);
    }

    /**
     * Записывает сообщение уровня notice.
     */
    public function notice(string|Stringable $message, string $indexName = 'error', array $context = []): void
    {
        $this->write('notice', $message, $indexName, $context);
    }

    /**
     * Записывает сообщение уровня info.
     */
    public function info(string|Stringable $message, string $indexName = 'error', array $context = []): void
    {
        $this->write('info', $message, $indexName, $context);
    }

    /**
     * Записывает сообщение уровня debug.
     */
    public function debug(string|Stringable $message, string $indexName = 'error', array $context = []): void
    {
        $this->write('debug', $message, $indexName, $context);
    }

    /**
     * Записывает сообщение указанного уровня.
     */
    public function log(string $level, string|Stringable $message, string $indexName = 'error', array $context = []): void
    {
        $this->write($level, $message, $indexName, $context);
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
