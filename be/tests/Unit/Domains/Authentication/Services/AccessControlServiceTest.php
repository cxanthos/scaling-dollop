<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Authentication\Services;

use App\Domains\Authentication\Models\AuthenticatedUserModel;
use App\Domains\Authentication\Services\AccessControlService;
use App\Domains\Authentication\Services\AuthenticationService;
use App\Domains\Users\Models\UserRole;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class AccessControlServiceTest extends TestCase
{
    /**
     * @param string $role
     * @return array{0: string, 1: AccessControlService}
     */
    private function createJwtForRole(string $role): array
    {
        $user = new AuthenticatedUserModel(
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            role: UserRole::from($role),
        );

        $authService = new AuthenticationService('test_secret');
        $jwt = $authService->createToken($user);

        return [$jwt, new AccessControlService($authService)];
    }

    public function testIsEmployeeReturnsTrueForEmployee(): void
    {
        [$jwt, $accessControl] = $this->createJwtForRole('employee');
        $this->assertTrue($accessControl->isEmployee($jwt));
    }

    public function testIsEmployeeReturnsFalseForManager(): void
    {
        [$jwt, $accessControl] = $this->createJwtForRole('manager');
        $this->assertFalse($accessControl->isEmployee($jwt));
    }

    public function testIsManagerReturnsTrueForManager(): void
    {
        [$jwt, $accessControl] = $this->createJwtForRole('manager');
        $this->assertTrue($accessControl->isManager($jwt));
    }

    public function testIsManagerReturnsFalseForEmployee(): void
    {
        [$jwt, $accessControl] = $this->createJwtForRole('employee');
        $this->assertFalse($accessControl->isManager($jwt));
    }

    public function testIsEmployeeFromRequestExtractsJwtAndChecksRole(): void
    {
        [$jwt, $accessControl] = $this->createJwtForRole('employee');
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Bearer ' . $jwt);
        $this->assertTrue($accessControl->isEmployeeFromRequest($request));
    }

    public function testIsManagerFromRequestExtractsJwtAndChecksRole(): void
    {
        [$jwt, $accessControl] = $this->createJwtForRole('manager');
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Bearer ' . $jwt);
        $this->assertTrue($accessControl->isManagerFromRequest($request));
    }

    public function testGetAuthenticatedUserModelFromRequestReturnsUser(): void
    {
        [$jwt, $accessControl] = $this->createJwtForRole('employee');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Bearer ' . $jwt);
        $this->assertSame([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'employee',
        ], $accessControl->getAuthenticatedUserModelFromRequest($request)->toArray());
    }

    public function testExtractJwtFromRequestThrowsOnMissingHeader(): void
    {
        $this->expectException(InvalidArgumentException::class);

        [, $accessControl] = $this->createJwtForRole('employee');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('');
        $accessControl->isEmployeeFromRequest($request);
    }

    public function testExtractJwtFromRequestThrowsOnInvalidHeader(): void
    {
        $this->expectException(InvalidArgumentException::class);

        [, $accessControl] = $this->createJwtForRole('employee');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Token abc');
        $accessControl->isManagerFromRequest($request);
    }
}
