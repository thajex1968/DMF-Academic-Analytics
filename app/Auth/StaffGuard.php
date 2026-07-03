<?php

declare(strict_types=1);

namespace DMF\Auth;

use Dmf\Core\Auth\Guard;

/**
 * Concrete `dmf-core` Guard for staff login (FR-001, decisions/IDR-010) —
 * composes `StaffTokenManager` + `StaffRateLimiter`. `login()`/`user()`/
 * `logout()` are already correct in the abstract base; this class only
 * supplies the rate-limit bucket key.
 */
final class StaffGuard extends Guard
{
    /** @param array<string, mixed> $credentials */
    protected function throttleKey(array $credentials): string
    {
        return (string) ($credentials['username'] ?? '');
    }
}
