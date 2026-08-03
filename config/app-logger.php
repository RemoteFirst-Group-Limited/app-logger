<?php

return [
	"logger" => [
		"decorate" => env("APP_LOGGER_DECORATE", true),
	],
	"retry_jobs" => [
		"enabled" => env("APP_LOGGER_RETRY_JOBS_ENABLED", true),
		"index_name" => env("APP_LOGGER_RETRY_JOB_INDEX", "warning"),
	],
	"failed_jobs" => [
		"enabled" => env("APP_LOGGER_FAILED_JOBS_ENABLED", true),
		"index_name" => env("APP_LOGGER_FAILED_JOB_INDEX", "error"),
		"payload_limit_bytes" => (int)env("APP_LOGGER_FAILED_JOB_PAYLOAD_LIMIT_BYTES", 8192),
		"context_limit_bytes" => (int)env("APP_LOGGER_FAILED_JOB_CONTEXT_LIMIT_BYTES", 32768),
		"context_enrichers" => [],
	],
];
