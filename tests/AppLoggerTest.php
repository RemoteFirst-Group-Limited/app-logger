<?php

declare(strict_types=1);

namespace AppLogger\Tests;

use AppLogger\Logging\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

final class AppLoggerTest extends TestCase
{
    public function testErrorUsesDefaultIndexName(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $appLogger = new Logger($logger);

        $logger->expects($this->once())
            ->method('log')
            ->with('error', 'Ошибка по умолчанию', ['index_name' => 'error']);

        $appLogger->error('Ошибка по умолчанию');
    }

    public function testLogProxiesLevelAndAddsDefaultErrorIndexName(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $appLogger = new Logger($logger);

        $logger->expects($this->once())
            ->method('log')
            ->with('warning', 'Проксирование уровня', [
                'request_id' => 'req-1',
                'index_name' => 'error',
            ]);

        $appLogger->log('warning', 'Проксирование уровня', [
            'request_id' => 'req-1',
        ]);
    }

    public function testPassthroughMethodsAreDelegatedWithOriginalArguments(): void
    {
        $baseLogger = new FakeLogger();
        $appLogger = new Logger($baseLogger);

        $result = $appLogger->passthrough('alpha', 7);

        $this->assertSame('alpha:7', $result);
        $this->assertSame([
            ['method' => 'passthrough', 'args' => ['alpha', 7]],
        ], $baseLogger->methodCalls);
    }

    public function testFluentChainDoesNotBreakAndPreservesIndexNameInjection(): void
    {
        $baseLogger = new FakeLogger();
        $appLogger = new Logger($baseLogger);

        $appLogger
            ->channel('security')
            ->withContext(['request_id' => 'req-42'])
            ->forTicket('Цепочка работает', 'audit', ['user_id' => 100]);

        $this->assertSame([
            ['method' => 'channel', 'args' => ['security']],
            ['method' => 'withContext', 'args' => [['request_id' => 'req-42']]],
        ], $baseLogger->methodCalls);

        $this->assertSame([
            [
                'level' => 'error',
                'message' => 'Цепочка работает',
                'context' => [
                    'user_id' => 100,
                    'index_name' => 'audit',
                ],
            ],
        ], $baseLogger->logs);
    }
    public function testMagicCallPassthroughWorksForLogManagerLikeObjects(): void
    {
        $baseLogger = new MagicLogger();
        $appLogger = new Logger($baseLogger);

        $appLogger
            ->withContext(['trace_id' => 't-1'])
            ->forTicket('Magic call', 'ops');

        $this->assertSame([
            ['method' => 'withContext', 'args' => [['trace_id' => 't-1']]],
        ], $baseLogger->methodCalls);

        $this->assertSame([
            [
                'level' => 'error',
                'message' => 'Magic call',
                'context' => ['index_name' => 'ops'],
            ],
        ], $baseLogger->logs);
    }

}

final class FakeLogger implements LoggerInterface
{
    /** @var array<int, array{method: string, args: array<int, mixed>}> */
    public array $methodCalls = [];

    /** @var array<int, array{level: string, message: string|Stringable, context: array<string, mixed>}> */
    public array $logs = [];

    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => (string) $level,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function passthrough(string $first, int $second): string
    {
        $this->methodCalls[] = ['method' => 'passthrough', 'args' => [$first, $second]];

        return sprintf('%s:%d', $first, $second);
    }

    public function channel(string $name): self
    {
        $this->methodCalls[] = ['method' => 'channel', 'args' => [$name]];

        return $this;
    }

    public function withContext(array $context): self
    {
        $this->methodCalls[] = ['method' => 'withContext', 'args' => [$context]];

        return $this;
    }
}


final class MagicLogger implements LoggerInterface
{
    /** @var array<int, array{method: string, args: array<int, mixed>}> */
    public array $methodCalls = [];

    /** @var array<int, array{level: string, message: string|Stringable, context: array<string, mixed>}> */
    public array $logs = [];

    public function __call(string $method, array $arguments): mixed
    {
        $this->methodCalls[] = ['method' => $method, 'args' => $arguments];

        return $this;
    }

    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => (string) $level,
            'message' => $message,
            'context' => $context,
        ];
    }
}
