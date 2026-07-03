<?php

declare(strict_types=1);

/**
 * Database configuration.
 *
 * Shape matches Dmf\Core\Database\Connection's constructor exactly
 * (host, database, username, password, port, options) — see
 * docs/03-Database-Design.md and dmf-core's Connection class.
 *
 * All credentials come from environment variables. Never hardcode
 * credentials here.
 */
return [
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'port'     => (int) (getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_NAME') ?: '',
    'username' => getenv('DB_USER') ?: '',
    'password' => getenv('DB_PASS') ?: '',
    'charset'  => 'utf8mb4',
    'options'  => [],
];
