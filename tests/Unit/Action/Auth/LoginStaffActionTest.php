<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Action\Auth;

use DMF\Action\Auth\LoginStaffAction;
use Dmf\Core\Auth\Guard;
use Dmf\Core\Exception\AuthException;
use Dmf\Core\Http\Request;
use PHPUnit\Framework\TestCase;

final class LoginStaffActionTest extends TestCase
{
    public function testSuccessfulLoginReturnsTheIssuedToken(): void
    {
        $guard = $this->createMock(Guard::class);
        $guard->expects(self::once())
            ->method('login')
            ->with(['username' => 'teacher01', 'password' => 'correct-password'])
            ->willReturn('signed.token.value');

        $action = new LoginStaffAction($guard);
        $request = new Request('POST', 'login_staff', ['username' => 'teacher01', 'password' => 'correct-password']);

        $response = $action($request);

        self::assertSame(200, $response->statusCode());
        self::assertSame(['token' => 'signed.token.value'], $response->data());
    }

    public function testFailedLoginPropagatesTheGuardsAuthException(): void
    {
        $guard = $this->createMock(Guard::class);
        $guard->method('login')->willThrowException(AuthException::invalidCredentials());

        $action = new LoginStaffAction($guard);
        $request = new Request('POST', 'login_staff', ['username' => 'teacher01', 'password' => 'wrong']);

        $this->expectException(AuthException::class);
        $action($request);
    }

    public function testMissingCredentialsAreForwardedAsEmptyStringsNotFatalErrors(): void
    {
        $guard = $this->createMock(Guard::class);
        $guard->expects(self::once())
            ->method('login')
            ->with(['username' => '', 'password' => ''])
            ->willThrowException(AuthException::invalidCredentials());

        $action = new LoginStaffAction($guard);
        $request = new Request('POST', 'login_staff', []);

        $this->expectException(AuthException::class);
        $action($request);
    }
}
