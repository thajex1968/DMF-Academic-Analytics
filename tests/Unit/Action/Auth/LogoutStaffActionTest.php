<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Action\Auth;

use DMF\Action\Auth\LogoutStaffAction;
use Dmf\Core\Auth\Guard;
use Dmf\Core\Http\Request;
use PHPUnit\Framework\TestCase;

final class LogoutStaffActionTest extends TestCase
{
    public function testLogoutWithATokenRevokesItAndReturnsSuccess(): void
    {
        $guard = $this->createMock(Guard::class);
        $guard->expects(self::once())->method('logout')->with('some-token');

        $action = new LogoutStaffAction($guard);
        $request = new Request('POST', 'logout_staff', [], 'some-token');

        $response = $action($request);

        self::assertSame(200, $response->statusCode());
        self::assertSame(['success' => true], $response->data());
    }

    public function testLogoutWithoutATokenStillSucceedsWithoutCallingTheGuard(): void
    {
        $guard = $this->createMock(Guard::class);
        $guard->expects(self::never())->method('logout');

        $action = new LogoutStaffAction($guard);
        $request = new Request('POST', 'logout_staff', []);

        $response = $action($request);

        self::assertSame(200, $response->statusCode());
        self::assertSame(['success' => true], $response->data());
    }
}
