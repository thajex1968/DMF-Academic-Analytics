<?php

declare(strict_types=1);

/**
 * Application bootstrap.
 *
 * Loads the environment, then assembles the application's configuration
 * bag from config/*.php and the DLAP_-prefixed environment namespace
 * (docs/02-System-Architecture.md §16, decisions/IDR-006). Returns a single
 * Dmf\Core\Config\Config instance — every later bootstrap step (database
 * connection, logger, auth) reads from this, never from getenv() or $_ENV
 * directly.
 *
 * This file has no return-type declaration because it is a script, not a
 * class or function — see docs/Naming-Convention.md §2 for why the rest of
 * this codebase is typed and this bootstrap entry point is the exception
 * PHP's own top-level-script model requires.
 */

use DMF\Config\EnvironmentLoader;
use Dmf\Core\Config\Config;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

EnvironmentLoader::load($root . '/.env');

$config = Config::fromArray([
    'app'      => require $root . '/config/app.php',
    'database' => require $root . '/config/database.php',
    'auth'     => require $root . '/config/auth.php',
    'ai'       => require $root . '/config/ai.php',
    'dlap'     => Config::fromEnvironment('DLAP_')->all(),
]);

/** @var array<string, mixed> $appConfig */
$appConfig = $config->get('app', []);
date_default_timezone_set((string) ($appConfig['timezone'] ?? 'Asia/Bangkok'));

return $config;
