# Laravel App Logger

`remotefirst-group-limited/app-logger` — публичный Composer-пакет с единым строгим фасадом `AppLogger` и реализацией `Logger` для Laravel 10/11.

Пакет не добавляет новые каналы или драйверы логирования. Он оборачивает стандартный `Psr\Log\LoggerInterface`, всегда добавляет `index_name` в контекст для бизнес-методов и проксирует неизвестные методы (`channel`, `stack`, `driver`, `withContext` и т.д.) к внутреннему Laravel logger/manager.

## Особенности

- Строгие сигнатуры с обязательным порядком аргументов: `message`, `indexName`, `context`.
- Значение `index_name` по умолчанию: `error`.
- `index_name` всегда устанавливается в контекст и перезаписывает существующее значение.
- Интеграция через DI и Facade.
- Fluent-цепочки не ломаются: если passthrough-метод возвращает логгер, он снова оборачивается в `AppLogger\Logging\Logger`.

Service provider подключается автоматически через Laravel auto-discovery.

## Использование

### Через DI

```php
use AppLogger\Logging\Logger;

final class ReportService
{
    public function __construct(private readonly Logger $logger)
    {
    }

    public function handle(): void
    {
        $this->logger->error('Ошибка обработки');
        $this->logger->info('Отчёт создан', 'reports', ['report_id' => 10]);
    }
}
```

### Через Facade

```php
use AppLogger\Facades\AppLogger;

AppLogger::warning('Подозрительное действие', 'security', ['user_id' => 42]);
```

## API

Строго типизированные бизнес-сигнатуры:

- `emergency(string|\Stringable $message, string $indexName = 'error', array $context = []): void`
- `alert(string|\Stringable $message, string $indexName = 'error', array $context = []): void`
- `critical(string|\Stringable $message, string $indexName = 'error', array $context = []): void`
- `error(string|\Stringable $message, string $indexName = 'error', array $context = []): void`
- `warning(string|\Stringable $message, string $indexName = 'error', array $context = []): void`
- `notice(string|\Stringable $message, string $indexName = 'error', array $context = []): void`
- `info(string|\Stringable $message, string $indexName = 'error', array $context = []): void`
- `debug(string|\Stringable $message, string $indexName = 'error', array $context = []): void`
- `log(string $level, string|\Stringable $message, string $indexName = 'error', array $context = []): void`

## Тесты

```bash
./vendor/bin/phpunit
./vendor/bin/pest
```


Остальные методы Laravel logger/manager доступны через proxy (`__call`) и делегируются 1:1 на внутренний объект.
