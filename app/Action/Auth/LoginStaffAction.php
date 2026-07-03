<?php

declare(strict_types=1);

namespace DMF\Action\Auth;

use Dmf\Core\Auth\Guard;
use Dmf\Core\Http\Request;
use Dmf\Core\Http\Response;

/**
 * `POST action=login_staff` (FR-001, decisions/IDR-010 §8). Thin HTTP
 * handler — all business logic (credential verification, rate limiting,
 * token issuance) lives in `Guard`/`TokenManager`/`RateLimiter`
 * (Architecture Rule: no business logic in the UI/handler layer). An empty
 * or wrong username/password, a locked account, an inactive account, and a
 * soft-deleted account all surface as the same `AuthException` from
 * `Guard::login()` — `Dmf\Core\Http\Router::dispatch()` already converts
 * that into the correctly-coded JSON error response (401 or 429), so this
 * class does not need its own try/catch.
 */
final class LoginStaffAction
{
    public function __construct(
        private readonly Guard $guard,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $username = (string) $request->input('username', '');
        $password = (string) $request->input('password', '');

        $token = $this->guard->login(['username' => $username, 'password' => $password]);

        return Response::ok(['token' => $token]);
    }
}
