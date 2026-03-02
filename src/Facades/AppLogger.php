<?php

declare(strict_types=1);

namespace AppLogger\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void emergency(string|\Stringable $message, array $context = [])
 * @method static void alert(string|\Stringable $message, array $context = [])
 * @method static void critical(string|\Stringable $message, array $context = [])
 * @method static void error(string|\Stringable $message, array $context = [])
 * @method static void forTicket(string|\Stringable $message, string $indexName, array $context = [])
 * @method static void warning(string|\Stringable $message, array $context = [])
 * @method static void notice(string|\Stringable $message, array $context = [])
 * @method static void info(string|\Stringable $message, array $context = [])
 * @method static void debug(string|\Stringable $message, array $context = [])
 * @method static void log(mixed $level, string|\Stringable $message, array $context = [])
 * @method static \AppLogger\Logging\Logger channel(string|null $channel = null)
 * @method static \AppLogger\Logging\Logger stack(array $channels, string|null $channel = null)
 * @method static \AppLogger\Logging\Logger driver(string|null $driver = null)
 * @method static \AppLogger\Logging\Logger build(array $config)
 * @method static \AppLogger\Logging\Logger withContext(array $context = [])
 * @method static \AppLogger\Logging\Logger withoutContext()
 * @method static mixed listen(\Closure $callback)
 *
 * @see \AppLogger\Logging\Logger
 */
final class AppLogger extends Facade
{
    /**
     * Возвращает ключ доступа к сервису AppLogger в контейнере.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'app_logger';
    }
}
