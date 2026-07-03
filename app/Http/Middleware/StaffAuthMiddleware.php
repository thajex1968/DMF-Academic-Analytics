<?php

declare(strict_types=1);

namespace DMF\Http\Middleware;

use Dmf\Core\Auth\Guard;
use Dmf\Core\Http\Middleware\AuthMiddleware;

/**
 * Concrete `dmf-core` AuthMiddleware for staff routes (FR-001/FR-002,
 * decisions/IDR-010). `handle()`/`isAuthorized()` are already correct in
 * the abstract base; this class only exposes `$requiredRole` through the
 * constructor so a route can require a specific role (e.g. `new
 * StaffAuthMiddleware($guard, 'admin')`) without a new subclass per role —
 * "future-ready for additional roles." Leaving `$requiredRole` at its
 * default `''` (any authenticated principal) is what the dashboard route
 * uses today.
 */
final class StaffAuthMiddleware extends AuthMiddleware
{
    public function __construct(Guard $guard, string $requiredRole = '')
    {
        parent::__construct($guard);

        $this->requiredRole = $requiredRole;
    }
}
