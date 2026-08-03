<?php

declare(strict_types=1);

namespace AppLogger\Queue;

use Illuminate\Contracts\Encryption\Encrypter;
use Throwable;

/**
 * Извлекает объект задания из стандартного Laravel queue payload.
 */
class QueuedJobResolver {
	public function __construct(private readonly ?Encrypter $encrypter = null) {
	}

	/**
	 * Извлечь объект задания.
	 *
	 * @param array<string, mixed> $payload Queue payload.
	 */
	public function resolve(array $payload): ?object {
		$command = $payload["data"]["command"] ?? null;
		if (!is_string($command) || $command === "") {
			return null;
		}

		try {
			$serialized = str_starts_with($command, "O:")
				? $command
				: $this->decrypt($command);
			$job = unserialize($serialized);

			return is_object($job) ? $job : null;
		} catch (Throwable) {
			return null;
		}
	}

	/**
	 * Расшифровать сериализованное задание.
	 */
	private function decrypt(string $command): string {
		if (!$this->encrypter instanceof Encrypter) {
			return "";
		}

		return (string)$this->encrypter->decrypt($command);
	}
}
