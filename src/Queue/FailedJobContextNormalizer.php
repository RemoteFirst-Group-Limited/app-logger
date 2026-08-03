<?php

declare(strict_types=1);

namespace AppLogger\Queue;

use BackedEnum;
use DateTimeInterface;
use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;
use SplObjectStorage;
use Throwable;

/**
 * Нормализует свойства задания для диагностического лога.
 */
class FailedJobContextNormalizer {
	private const MAX_DEPTH = 6;

	public function __construct(
		private readonly int $payloadLimitBytes,
		private readonly int $contextLimitBytes,
	) {
	}

	/**
	 * Извлечь нормализованные свойства задания.
	 *
	 * @return array<string, mixed>
	 */
	public function normalizeJob(object $job): array {
		try {
			$seen = new SplObjectStorage();
			return $this->limitContext($this->normalizeObject($job, 0, $seen));
		} catch (Throwable) {
			return [];
		}
	}

	/**
	 * Нормализовать объединённый контекст задания и обогатителей.
	 *
	 * @param array<string, mixed> $context
	 * @return array<string, mixed>
	 */
	public function normalizeContext(array $context): array {
		$seen = new SplObjectStorage();

		return $this->limitContext($this->normalizeArray($context, 0, $seen));
	}

	/**
	 * Ограничить обязательный диагностический текст.
	 */
	public function normalizeText(string $value): string {
		if (strlen($value) <= $this->payloadLimitBytes) {
			return $value;
		}

		$suffix = "… [обрезано]";
		$length = max(0, $this->payloadLimitBytes - strlen($suffix));

		return mb_strcut($value, 0, $length, "UTF-8") . $suffix;
	}

	/**
	 * Нормализовать объект.
	 *
	 * @param SplObjectStorage<object, null> $seen
	 * @return array<string, mixed>
	 */
	private function normalizeObject(object $value, int $depth, SplObjectStorage $seen): array {
		if ($depth >= self::MAX_DEPTH || $seen->contains($value)) {
			return ["truncated" => true];
		}

		$seen->attach($value);
		$result = [];
		foreach ($this->properties($value) as $property) {
			if (!$property->isInitialized($value) || $property->isStatic()) {
				continue;
			}
			$key = $this->snake($property->getName());
			$normalized = $this->normalizeValue($property->getValue($value), $key, $depth + 1, $seen);
			if ($normalized !== null) {
				$result[$key] = $normalized;
			}
		}

		return $result;
	}

	/**
	 * Получить свойства объекта, включая закрытые свойства родителей.
	 *
	 * @return array<int, ReflectionProperty>
	 */
	private function properties(object $value): array {
		$properties = [];
		$class = new ReflectionObject($value);
		while ($class instanceof ReflectionClass) {
			foreach ($class->getProperties() as $property) {
				$properties[$class->getName() . "::" . $property->getName()] = $property;
			}
			$class = $class->getParentClass();
		}

		return array_values($properties);
	}

	/**
	 * Нормализовать произвольное значение.
	 *
	 * @param SplObjectStorage<object, null> $seen
	 */
	private function normalizeValue(mixed $value, string|int $key, int $depth, SplObjectStorage $seen): mixed {
		$normalized = match (true) {
			$value instanceof BackedEnum => $value->value,
			$value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
			is_array($value) => $this->normalizeArray($value, $depth, $seen),
			is_scalar($value) => $value,
			default => null,
		};

		return $key === "payload" ? $this->limitPayload($normalized) : $normalized;
	}

	/**
	 * Нормализовать массив.
	 *
	 * @param array<mixed> $value
	 * @param SplObjectStorage<object, null> $seen
	 * @return array<mixed>
	 */
	private function normalizeArray(array $value, int $depth, SplObjectStorage $seen): array {
		if ($depth >= self::MAX_DEPTH) {
			return ["truncated" => true];
		}

		$result = [];
		foreach ($value as $key => $item) {
			$normalizedKey = is_string($key) ? $this->snake($key) : $key;
			$normalized = $this->normalizeValue($item, $normalizedKey, $depth + 1, $seen);
			if ($normalized !== null) {
				$result[$normalizedKey] = $normalized;
			}
		}

		return $result;
	}

	/**
	 * Ограничить JSON-представление payload.
	 */
	private function limitPayload(mixed $payload): mixed {
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if (!is_string($json) || strlen($json) <= $this->payloadLimitBytes) {
			return $payload;
		}

		$previewLength = max(0, $this->payloadLimitBytes - 160);
		do {
			$limited = [
				"truncated" => true,
				"original_size_bytes" => strlen($json),
				"preview" => mb_strcut($json, 0, $previewLength, "UTF-8"),
			];
			$previewLength = max(0, $previewLength - 64);
		} while ($this->encodedLength($limited) > $this->payloadLimitBytes && $previewLength > 0);

		return $limited;
	}

	/**
	 * Рассчитать длину JSON-представления.
	 *
	 * @param array<string, mixed> $value
	 */
	private function encodedLength(array $value): int {
		$json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return is_string($json) ? strlen($json) : 0;
	}

	/**
	 * Ограничить общий размер контекста.
	 *
	 * @param array<string, mixed> $context
	 * @return array<string, mixed>
	 */
	private function limitContext(array $context): array {
		$result = [];
		$truncated = false;
		$limitWithoutMarker = max(0, $this->contextLimitBytes - 48);
		foreach ($context as $key => $value) {
			$candidate = [...$result, $key => $value];
			if ($this->encodedLength($candidate) <= $limitWithoutMarker) {
				$result[$key] = $value;
				continue;
			}
			$truncated = true;
		}

		return $truncated ? [...$result, "context_truncated" => true] : $result;
	}

	/**
	 * Преобразовать имя поля в snake_case.
	 */
	private function snake(string $value): string {
		$value = preg_replace("/(?<!^)[A-Z]/", "_$0", $value) ?? $value;

		return strtolower($value);
	}
}
