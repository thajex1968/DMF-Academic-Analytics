<?php

declare(strict_types=1);

/**
 * Application-level configuration.
 *
 * All environment-specific values come from environment variables —
 * see docs/Architecture-Principles.md §8 (Backward Compatibility) and
 * docs/01-PRD.md §20 (Security NFR). Never hardcode a value here that
 * should differ between environments.
 *
 * 'version' defaults to the project-root VERSION file (the single source
 * of truth for the shipped release — see docs/Release-Notes.md), with
 * APP_VERSION as an override for build pipelines that need to inject a
 * specific value without editing that file.
 */
return [
    'name'     => getenv('APP_NAME') ?: 'DMF Learning Analytics Platform',
    'version'  => getenv('APP_VERSION') ?: readVersionFile(),
    'env'      => getenv('APP_ENV') ?: 'production',
    'debug'    => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL),
    'timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Bangkok',
    'locale'   => getenv('APP_LOCALE') ?: 'th',
];

/**
 * Read the project-root VERSION file, falling back to '0.0.0' if it is
 * missing — a missing VERSION file should never be a fatal bootstrap error.
 */
function readVersionFile(): string
{
    $path = dirname(__DIR__) . '/VERSION';
    if (!is_file($path) || !is_readable($path)) {
        return '0.0.0';
    }

    $contents = file_get_contents($path);
    return $contents === false ? '0.0.0' : trim($contents);
}
