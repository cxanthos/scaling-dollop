<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Vacations\Models;

use App\Domains\Users\Models\UserModel;
use App\Domains\Users\Models\UserRole;
use App\Domains\Vacations\Models\VacationModel;
use App\Domains\Vacations\Models\VacationStatus;
use DateInterval;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class VacationModelTest extends TestCase
{
    public function testValidConstructionMinimalFields(): void
    {
        $from = new DateTime()->add(new DateInterval('P1D'));
        $to = new DateTime()->add(new DateInterval('P2D'));

        $model = new VacationModel(null, 1, null, $from, $to, 'Holiday');

        $this->assertNull($model->getId());
        $this->assertSame(1, $model->getUserId());
        $this->assertNull($model->getUser());
        $this->assertEquals($from, $model->getFrom());
        $this->assertEquals($to, $model->getTo());
        $this->assertSame('Holiday', $model->getReason());
        $this->assertSame(VacationStatus::Pending, $model->getStatus());
        $this->assertNull($model->getAuthorizedBy());
        $this->assertNull($model->getCreatedAt());
        $this->assertNull($model->getUpdatedAt());
    }

    public function testValidConstructionAllFields(): void
    {
        $user = new UserModel(null, 'employee@example.com', null, 'Employee Name', '1234567', UserRole::Employee);
        $from = new DateTime()->add(new DateInterval('P1D'));
        $to = new DateTime()->add(new DateInterval('P2D'));
        $today = new DateTime();
        $createdAt = $today;
        $updatedAt = $today;

        $model = new VacationModel(5, 2, $user, $from, $to, 'Trip', VacationStatus::Approved, 99, $createdAt, $updatedAt);

        $this->assertSame(5, $model->getId());
        $this->assertSame(2, $model->getUserId());
        $this->assertEquals($user, $model->getUser());
        $this->assertEquals($from, $model->getFrom());
        $this->assertEquals($to, $model->getTo());
        $this->assertSame('Trip', $model->getReason());
        $this->assertSame(VacationStatus::Approved, $model->getStatus());
        $this->assertSame(99, $model->getAuthorizedBy());
        $this->assertEquals($createdAt, $model->getCreatedAt());
        $this->assertEquals($updatedAt, $model->getUpdatedAt());
    }

    public function testEmptyReasonThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reason must not be empty.');

        $from = new DateTime()->add(new DateInterval('P1D'));
        $to = new DateTime()->add(new DateInterval('P2D'));
        new VacationModel(null, 1, null, $from, $to, '   ');
    }

    public function testFromAfterToThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "from" date must be earlier than the "to" date.');

        $from = new DateTime()->add(new DateInterval('P2D'));
        $to = new DateTime()->add(new DateInterval('P1D'));
        new VacationModel(null, 1, null, $from, $to, 'Trip');
    }

    public function testDurationExceedsMaxThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Vacation duration cannot be more than 60 days.');

        $from = new DateTime()->add(new DateInterval('P1D'));
        $to = new DateTime()->add(new DateInterval('P'.(VacationModel::MAX_VACATION_DAYS + 2).'D'));
        new VacationModel(null, 1, null, $from, $to, 'Trip');
    }

    public function testToArrayAndJsonSerialize(): void
    {
        $user = new UserModel(null, 'employee@example.com', null, 'Employee Name', '1234567', UserRole::Employee);
        $from = new DateTime()->add(new DateInterval('P1D'));
        $to = new DateTime()->add(new DateInterval('P2D'));
        $today = new DateTime();
        $createdAt = $today;
        $updatedAt = $today;

        $model = new VacationModel(5, 2, $user, $from, $to, 'Trip', VacationStatus::Approved, 99, $createdAt, $updatedAt);

        $expected = [
            'id' => 5,
            'userId' => 2,
            'user' => [
                'id' => null,
                'email' => 'employee@example.com',
                'name' => 'Employee Name',
                'employeeCode' => '1234567',
                'role' => 'employee',
                'createdAt' => null,
                'updatedAt' => null,
            ],
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'reason' => 'Trip',
            'status' => VacationStatus::Approved->value,
            'authorizedBy' => 99,
            'createdAt' => $createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $updatedAt->format('Y-m-d H:i:s'),
        ];
        $this->assertSame($expected, $model->toArray());
        $this->assertSame($expected, $model->jsonSerialize());
    }
}
