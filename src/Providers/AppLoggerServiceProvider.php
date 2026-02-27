<?php

declare(strict_types=1);

namespace AppLogger\Providers;

use AppLogger\Logging\Logger;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

final class AppLoggerServiceProvider extends ServiceProvider
{
    /**
     * Регистрирует зависимости пакета в контейнере.
     */
    public function register(): void
    {
        $this->app->singleton('app_logger', function ($app): Logger {
            return new Logger($app->make(LoggerInterface::class));
        });

        $this->app->alias('app_logger', Logger::class);
    }
}
