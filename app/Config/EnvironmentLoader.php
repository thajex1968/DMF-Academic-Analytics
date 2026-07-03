<?php

declare(strict_types=1);

namespace DMF\Config;

/**
 * Loads KEY=VALUE pairs from a .env-style file into the process environment.
 *
 * Populates getenv()/putenv() and $_ENV/$_SERVER together, because this
 * codebase reads environment variables through both mechanisms: config/*.php
 * files call getenv() directly (matching the dmf-template convention), while
 * Dmf\Core\Config\Config::fromEnvironment() reads $_ENV. A variable already
 * present in the environment is never overwritten — on production shared
 * hosting, DirectAdmin/cPanel sets real environment variables, and this
 * loader's only job there is to be a no-op; a committed .env.example or a
 * developer's local .env can never shadow a value the host already set.
 *
 * See decisions/IDR-004-custom-env-loader.md for why this is a small,
 * dependency-free loader rather than a Composer package.
 */
final class EnvironmentLoader
{
    /**
     * Load a .env file, if it exists, into the process environment.
     *
     * Silently does nothing if the file is missing or unreadable — a
     * missing .env is expected and correct on a host that already sets
     * real environment variables.
     */
    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            self::loadLine($line);
        }
    }

    private static function loadLine(string $line): void
    {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            return;
        }

        if (!str_contains($line, '=')) {
            return;
        }

        [$name, $rawValue] = explode('=', $line, 2);
        $name = trim($name);
        if ($name === '') {
            return;
        }

        self::set($name, self::stripQuotes(trim($rawValue)));
    }

    private static function stripQuotes(string $value): string
    {
        $length = strlen($value);
        if ($length < 2) {
            return $value;
        }

        $first = $value[0];
        $last = $value[$length - 1];
        $isQuoted = ($first === '"' && $last === '"') || ($first === "'" && $last === "'");

        return $isQuoted ? substr($value, 1, -1) : $value;
    }

    private static function set(string $name, string $value): void
    {
        if (getenv($name) !== false || array_key_exists($name, $_ENV)) {
            return;
        }

        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
