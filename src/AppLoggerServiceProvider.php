<?php

declare(strict_types=1);

namespace AppLogger;

use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

final class AppLoggerServiceProvider extends ServiceProvider
{
    /**
     * Регистрирует зависимости пакета в контейнере.
     */
    public function register(): void
    {
        $this->app->singleton('app_logger', function ($app): AppLogger {
            return new AppLogger($app->make(LoggerInterface::class));
        });

        $this->app->alias('app_logger', AppLogger::class);
    }
}
