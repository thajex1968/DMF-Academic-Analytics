<?php

declare(strict_types=1);

/**
 * Authentication and rate-limiting configuration.
 *
 * Values match docs/01-PRD.md FR-001's business rule exactly: 5 consecutive
 * failed login attempts locks an account for 5 minutes; issued tokens expire
 * after 8 hours of inactivity. Do not change the defaults below without
 * updating FR-001 first — see docs/Architecture-Principles.md §1 (SSOT).
 */
return [
    'token_secret'    => getenv('TOKEN_SECRET') ?: '',
    'token_ttl'       => (int) (getenv('TOKEN_TTL') ?: 28800),
    'max_login_fail'  => (int) (getenv('MAX_LOGIN_FAIL') ?: 5),
    'lockout_seconds' => (int) (getenv('LOCKOUT_SEC') ?: 300),
];
