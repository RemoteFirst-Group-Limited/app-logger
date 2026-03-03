# Laravel App Logger

`remotefirst-group-limited/app-logger` — публичный Composer-пакет с декоратором `LoggerDecorator` для Laravel 10/11.

Пакет не добавляет новые каналы или драйверы логирования. Он оборачивает стандартный `Psr\Log\LoggerInterface`, всегда добавляет `index_name` в контекст и проксирует неизвестные методы (`channel`, `stack`, `driver`, `withContext` и т.д.) к внутреннему logger/manager.

## Особенности

- Строгие сигнатуры: для стандартных уровней `message`, `context`; для ticket-логов `message`, `indexName`, `context`.
- Значение `index_name` по умолчанию: `error`.
- `index_name` всегда устанавливается в контекст и перезаписывает существующее значение.
- Интеграция через DI (без Facade и Service Provider).
- Fluent-цепочки не ломаются: если passthrough-метод возвращает логгер, он снова оборачивается в `AppLogger\LoggerDecorator`.


## Использование

### Через DI

```php
use AppLogger\LoggerDecorator;

final class ReportService
{
    public function __construct(private readonly LoggerDecorator $logger)
    {
    }

    public function handle(): void
    {
        $this->logger->error('Ошибка обработки');
        $this->logger->forTicket('Отчёт создан', 'reports', ['report_id' => 10]);
    }
}
```

## API

Строго типизированные бизнес-сигнатуры:

- `emergency(string|\Stringable $message, array $context = []): void`
- `alert(string|\Stringable $message, array $context = []): void`
- `critical(string|\Stringable $message, array $context = []): void`
- `error(string|\Stringable $message, array $context = []): void`
- `forTicket(string|\Stringable $message, string $indexName, array $context = []): void`
- `warning(string|\Stringable $message, array $context = []): void`
- `notice(string|\Stringable $message, array $context = []): void`
- `info(string|\Stringable $message, array $context = []): void`
- `debug(string|\Stringable $message, array $context = []): void`
- `log(mixed $level, string|\Stringable $message, array $context = []): void`

## Тесты

```bash
./vendor/bin/phpunit
./vendor/bin/pest
```


Остальные методы Laravel logger/manager доступны через proxy (`__call`) и делегируются 1:1 на внутренний объект.
