# Laravel App Logger

`remotefirst-group-limited/app-logger` — публичный Composer-пакет с единым строгим `AppLogger` для Laravel 10/11.

Пакет не добавляет новые каналы или драйверы логирования. Он просто оборачивает стандартный `Psr\Log\LoggerInterface` и всегда добавляет `index_name` в контекст.

## Особенности

- Строгие сигнатуры с обязательным порядком аргументов: `message`, `indexName`, `context`.
- Значение `index_name` по умолчанию: `error`.
- `index_name` всегда устанавливается в контекст и перезаписывает существующее значение.
- Интеграция через DI и Facade.

Service provider подключается автоматически через Laravel auto-discovery.

## Использование

### Через DI

```php
use AppLogger\AppLogger;

final class ReportService
{
    public function __construct(private readonly AppLogger $logger)
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

Поддерживаются только следующие сигнатуры:

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
