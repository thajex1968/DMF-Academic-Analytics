<?php

declare(strict_types=1);

namespace DMF\Auth;

use DMF\Repository\LoginRateLimitRepository;
use Dmf\Core\Auth\RateLimiter;

/**
 * Concrete `dmf-core` RateLimiter backed by `login_rate_limits` (FR-001,
 * decisions/IDR-010) — MySQL-backed, not `$_SESSION`-backed like
 * `grade.dmf.ac.th`'s `SessionRateLimiter`, per this module's stateless,
 * no-PHP-session architecture. Keyed on the plain `username` column value —
 * no scoping prefix, since no other principal type shares this table.
 */
final class StaffRateLimiter extends RateLimiter
{
    public function __construct(
        private readonly LoginRateLimitRepository $limits,
        int $maxAttempts,
        int $lockoutSeconds,
    ) {
        parent::__construct($maxAttempts, $lockoutSeconds);
    }

    protected function increment(string $key): int
    {
        $row = $this->limits->findByUsername($key);

        if ($row === null) {
            $this->limits->create(['username' => $key, 'failed_attempts' => 1]);

            return 1;
        }

        $attempts = (int) $row['failed_attempts'] + 1;
        $this->limits->update($row['id'], ['failed_attempts' => $attempts]);

        return $attempts;
    }

    protected function lockUntil(string $key, int $timestamp): void
    {
        $lockedUntil = date('Y-m-d H:i:s', $timestamp);
        $row = $this->limits->findByUsername($key);

        if ($row === null) {
            $this->limits->create(['username' => $key, 'failed_attempts' => 0, 'locked_until' => $lockedUntil]);

            return;
        }

        $this->limits->update($row['id'], ['locked_until' => $lockedUntil]);
    }

    protected function getLockExpiry(string $key): ?int
    {
        $row = $this->limits->findByUsername($key);

        if ($row === null || $row['locked_until'] === null) {
            return null;
        }

        $timestamp = strtotime((string) $row['locked_until']);

        return $timestamp !== false ? $timestamp : null;
    }

    protected function clear(string $key): void
    {
        $row = $this->limits->findByUsername($key);

        if ($row !== null) {
            $this->limits->update($row['id'], ['failed_attempts' => 0, 'locked_until' => null]);
        }
    }
}
