<?php

declare(strict_types=1);

namespace AppLogger\Tests;

use AppLogger\AppLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class AppLoggerTest extends TestCase
{
    public function testErrorUsesDefaultIndexName(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $appLogger = new AppLogger($logger);

        $logger->expects($this->once())
            ->method('log')
            ->with('error', 'Ошибка по умолчанию', ['index_name' => 'error']);

        $appLogger->error('Ошибка по умолчанию');
    }

    public function testErrorUsesExplicitIndexName(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $appLogger = new AppLogger($logger);

        $logger->expects($this->once())
            ->method('log')
            ->with('error', 'Явный индекс', ['index_name' => 'billing']);

        $appLogger->error('Явный индекс', 'billing');
    }

    public function testIndexNameIsOverriddenWhenAlreadyPresentInContext(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $appLogger = new AppLogger($logger);

        $logger->expects($this->once())
            ->method('log')
            ->with('error', 'Переопределение индекса', [
                'foo' => 'bar',
                'index_name' => 'audit',
            ]);

        $appLogger->error('Переопределение индекса', 'audit', [
            'foo' => 'bar',
            'index_name' => 'legacy',
        ]);
    }

    public function testLogProxiesLevelAndAddsIndexName(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $appLogger = new AppLogger($logger);

        $logger->expects($this->once())
            ->method('log')
            ->with('warning', 'Проксирование уровня', [
                'request_id' => 'req-1',
                'index_name' => 'security',
            ]);

        $appLogger->log('warning', 'Проксирование уровня', 'security', [
            'request_id' => 'req-1',
        ]);
    }
}
