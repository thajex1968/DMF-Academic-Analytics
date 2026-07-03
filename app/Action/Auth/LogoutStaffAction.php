<?php

declare(strict_types=1);

namespace DMF\Action\Auth;

use Dmf\Core\Auth\Guard;
use Dmf\Core\Http\Request;
use Dmf\Core\Http\Response;

/**
 * `POST action=logout_staff` (FR-001, decisions/IDR-010). `Guard::logout()`
 * calls `TokenManager::revoke()`, which is a documented no-op for this
 * stateless HMAC token (no denylist store exists — see IDR-010 §Consequences)
 * — logout is therefore primarily a client-side action (discarding the
 * token from `sessionStorage`); this endpoint exists so the client always
 * has a well-defined server round-trip to call, and so a future denylist
 * store has one integration point to extend, not two.
 */
final class LogoutStaffAction
{
    public function __construct(
        private readonly Guard $guard,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if ($request->hasToken()) {
            $this->guard->logout($request->bearerToken());
        }

        return Response::ok(['success' => true]);
    }
}
