<?php

declare(strict_types=1);

namespace DMF\Auth;

use DMF\Repository\StaffUserRepository;
use Dmf\Core\Auth\TokenManager;
use Dmf\Core\Exception\AuthException;
use Dmf\Core\Security\PasswordHasher;

/**
 * Concrete `dmf-core` TokenManager for `staff_users` (FR-001,
 * decisions/IDR-010). `authenticate()` is the only method this class must
 * supply — `verify()`/`revoke()` are already correct in the abstract base.
 *
 * Timing-safe by construction: `PasswordHasher::verify()` runs
 * unconditionally, even when the username doesn't resolve to a real row
 * (against a fixed dummy hash), so a nonexistent-username request and a
 * wrong-password request take roughly the same server time — a small,
 * genuine hardening `grade.dmf.ac.th`'s equivalent code does not do.
 */
final class StaffTokenManager extends TokenManager
{
    /**
     * A syntactically valid bcrypt hash that matches no real password —
     * used only to keep `PasswordHasher::verify()`'s cost constant when the
     * username lookup fails, never intended to be satisfiable.
     */
    private const DUMMY_HASH = '$2y$10$wJ7q8fQwZ9x2yV1kQhF8UuYFq0m6r0m6r0m6r0m6r0m6r0m6r0m6q';

    public function __construct(
        string $secret,
        int $ttlSeconds,
        private readonly StaffUserRepository $users,
        private readonly PasswordHasher $hasher,
    ) {
        parent::__construct($secret, $ttlSeconds);
    }

    /**
     * @param array<string, mixed> $credentials Must include `username`, `password`.
     * @throws AuthException On any invalid credential, inactive, or soft-deleted account.
     */
    public function authenticate(array $credentials): string
    {
        $username = (string) ($credentials['username'] ?? '');
        $password = (string) ($credentials['password'] ?? '');

        $user = $username !== '' ? $this->users->findByUsername($username) : null;
        $hash = $user !== null ? (string) $user['password_hash'] : self::DUMMY_HASH;

        $passwordValid = $this->hasher->verify($password, $hash);

        if (
            $user === null
            || $password === ''
            || !$passwordValid
            || (int) $user['is_active'] !== 1
            || $user['deleted_at'] !== null
        ) {
            throw AuthException::invalidCredentials();
        }

        return $this->issue([
            'sub' => (string) $user['id'],
            'role' => (string) $user['role'],
            'username' => (string) $user['username'],
            'display_name' => (string) $user['display_name'],
            'school_id' => (int) $user['school_id'],
        ]);
    }
}
