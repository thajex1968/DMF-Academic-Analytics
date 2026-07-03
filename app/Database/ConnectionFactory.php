<?php

declare(strict_types=1);

namespace DMF\Database;

use Dmf\Core\Config\Config;
use Dmf\Core\Database\Connection;

/**
 * Constructs the one `Dmf\Core\Database\Connection` a process needs, from
 * the `'database'` config group (`config/database.php`'s shape). Completes
 * decisions/IDR-005, which designed this class before any caller actually
 * needed one — every task before T2.7 received its `ConnectionInterface`
 * from a test double or an as-yet-unbuilt caller.
 *
 * Called once per process (once per CLI/cron script invocation; once per
 * HTTP request once the front controller exists — a later module). No
 * global registry, no singleton — the constructed `Connection` is passed
 * explicitly to whatever needs it, per IDR-005's "no Service Container yet"
 * reasoning.
 */
final class ConnectionFactory
{
    public static function fromConfig(Config $config): Connection
    {
        /** @var array<string, mixed> $database */
        $database = (array) $config->get('database', []);

        return new Connection(
            (string) ($database['host'] ?? 'localhost'),
            (string) ($database['database'] ?? ''),
            (string) ($database['username'] ?? ''),
            (string) ($database['password'] ?? ''),
            (int) ($database['port'] ?? 3306),
            (array) ($database['options'] ?? []),
        );
    }
}
