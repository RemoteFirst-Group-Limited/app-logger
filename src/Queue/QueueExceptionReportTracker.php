<?php

declare(strict_types=1);

namespace AppLogger\Queue;

use Throwable;
use WeakMap;

/**
 * Отслеживает исключения очереди, уже обработанные retry-логгером.
 */
class QueueExceptionReportTracker {
	/** @var WeakMap<Throwable, true> */
	private WeakMap $exceptions;

	public function __construct() {
		$this->exceptions = new WeakMap();
	}

	/**
	 * Запомнить обработанное исключение.
	 */
	public function remember(Throwable $exception): void {
		$this->exceptions[$exception] = true;
	}

	/**
	 * Однократно подтвердить обработку исключения.
	 */
	public function consume(Throwable $exception): bool {
		if (!isset($this->exceptions[$exception])) {
			return false;
		}

		unset($this->exceptions[$exception]);

		return true;
	}
}
