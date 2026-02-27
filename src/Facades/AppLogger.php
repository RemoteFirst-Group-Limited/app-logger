<?php

declare(strict_types=1);

namespace AppLogger\Facades;

use Illuminate\Support\Facades\Facade;

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
