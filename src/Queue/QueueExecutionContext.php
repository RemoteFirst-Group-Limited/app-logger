<?php

declare(strict_types=1);

namespace AppLogger\Queue;

/**
 * Хранит состояние выполнения текущей попытки задания очереди.
 */
class QueueExecutionContext {
	private bool $active = false;

	/**
	 * Отметить начало выполнения попытки.
	 */
	public function start(mixed $event = null): void {
		$this->active = true;
	}

	/**
	 * Отметить завершение выполнения попытки.
	 */
	public function finish(mixed $event = null): void {
		$this->active = false;
	}

	/**
	 * Проверить, выполняется ли сейчас задание очереди.
	 */
	public function isActive(): bool {
		return $this->active;
	}
}
