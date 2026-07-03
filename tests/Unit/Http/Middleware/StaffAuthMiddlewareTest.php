<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Http\Middleware;

use DMF\Http\Middleware\StaffAuthMiddleware;
use Dmf\Core\Auth\Guard;
use Dmf\Core\Auth\Principal;
use Dmf\Core\Exception\AuthException;
use Dmf\Core\Http\Request;
use Dmf\Core\Http\Response;
use PHPUnit\Framework\TestCase;

final class StaffAuthMiddlewareTest extends TestCase
{
    public function testRejectsARequestWithNoTokenBeforeEverCallingTheGuard(): void
    {
        $guard = $this->createMock(Guard::class);
        $guard->expects(self::never())->method('user');

        $middleware = new StaffAuthMiddleware($guard);
        $request = new Request('GET', 'dashboard_summary', []);

        $response = $middleware->handle(
            $request,
            static fn (Request $r): Response => Response::ok(['unreachable' => true]),
        );

        self::assertSame(401, $response->statusCode());
    }

    public function testRejectsAnInvalidOrExpiredToken(): void
    {
        $guard = $this->createMock(Guard::class);
        $guard->method('user')->willThrowException(AuthException::tokenExpired());

        $middleware = new StaffAuthMiddleware($guard);
        $request = new Request('GET', 'dashboard_summary', [], 'expired-token');

        $response = $middleware->handle(
            $request,
            static fn (Request $r): Response => Response::ok(['unreachable' => true]),
        );

        self::assertSame(401, $response->statusCode());
    }

    public function testAllowsAnyAuthenticatedPrincipalWhenNoRoleIsRequired(): void
    {
        $principal = new Principal('5', 'teacher', time(), time() + 28800);
        $guard = $this->createMock(Guard::class);
        $guard->method('user')->willReturn($principal);

        $middleware = new StaffAuthMiddleware($guard);
        $request = new Request('GET', 'dashboard_summary', [], 'valid-token');

        $response = $middleware->handle(
            $request,
            static fn (Request $r): Response => Response::ok(['reached' => true]),
        );

        self::assertSame(200, $response->statusCode());
        self::assertSame(['reached' => true], $response->data());
    }

    public function testRejectsAPrincipalWithoutTheRequiredRole(): void
    {
        $principal = new Principal('5', 'teacher', time(), time() + 28800);
        $guard = $this->createMock(Guard::class);
        $guard->method('user')->willReturn($principal);

        $middleware = new StaffAuthMiddleware($guard, 'admin');
        $request = new Request('GET', 'system_config', [], 'valid-token');

        $response = $middleware->handle(
            $request,
            static fn (Request $r): Response => Response::ok(['unreachable' => true]),
        );

        self::assertSame(403, $response->statusCode());
    }

    public function testAllowsAPrincipalWithTheRequiredRole(): void
    {
        $principal = new Principal('9', 'admin', time(), time() + 28800);
        $guard = $this->createMock(Guard::class);
        $guard->method('user')->willReturn($principal);

        $middleware = new StaffAuthMiddleware($guard, 'admin');
        $request = new Request('GET', 'system_config', [], 'valid-token');

        $response = $middleware->handle(
            $request,
            static fn (Request $r): Response => Response::ok(['reached' => true]),
        );

        self::assertSame(200, $response->statusCode());
    }
}
