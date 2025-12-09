<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Vacations\Repositories;

use App\Domains\Vacations\Models\VacationModel;
use App\Domains\Vacations\Models\VacationStatus;
use App\Domains\Vacations\Repositories\VacationsRepository;
use App\Shared\Database;
use DateMalformedStringException;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class VacationsRepositoryTest extends TestCase
{
    /**
     * @throws DateMalformedStringException
     */
    public function testCreateSuccess(): void
    {
        $expectedId = 1;
        $expectedUserId = 1;
        $expectedFrom = '2025-12-01';
        $expectedTo = '2025-12-05';
        $expectedReason = 'Family vacation';
        $expectedStatus = VacationStatus::Pending;
        $expectedCreatedAt = '2025-11-06 10:00:00';
        $expectedUpdatedAt = '2025-11-06 11:00:00';

        $dbMock = $this->createMock(Database::class);
        $repo = new VacationsRepository($dbMock);
        $vacation = new VacationModel(
            null,
            $expectedUserId,
            null,
            new DateTime($expectedFrom),
            new DateTime($expectedTo),
            $expectedReason,
            $expectedStatus,
            null,
            null,
            null
        );

        $dbMock->expects($this->once())
            ->method('execute')
            ->with(
                $this->callback(function (string $sql) {
                    return str_starts_with($sql, 'INSERT INTO vacations (user_id, `from`, `to`, reason, status)')
                        && str_ends_with($sql, 'VALUES (:user_id, :from, :to, :reason, :status)');
                }),
                [
                    'user_id' => $expectedUserId,
                    'from' => $expectedFrom,
                    'to' => $expectedTo,
                    'reason' => $expectedReason,
                    'status' => $expectedStatus->value,
                ]
            )
            ->willReturn(1);

        $dbMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn((string)$expectedId);

        $dbMock->expects($this->once())
            ->method('query')
            ->with(
                $this->equalTo('SELECT created_at, updated_at FROM vacations WHERE id = :id'),
                ['id' => $expectedId]
            )
            ->willReturn([
                ['created_at' => $expectedCreatedAt, 'updated_at' => $expectedUpdatedAt]
            ]);

        $created = $repo->create($vacation);

        $this->assertSame($expectedId, $created->getId());
        $this->assertSame($expectedUserId, $created->getUserId());
        $this->assertNull($created->getUser());
        $this->assertSame($expectedFrom, $created->getFrom()->format('Y-m-d'));
        $this->assertSame($expectedTo, $created->getTo()->format('Y-m-d'));
        $this->assertSame($expectedReason, $created->getReason());
        $this->assertSame($expectedStatus, $created->getStatus());
        $this->assertSame($expectedCreatedAt, $created->getCreatedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame($expectedUpdatedAt, $created->getUpdatedAt()?->format('Y-m-d H:i:s'));
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testFindByIdSuccess(): void
    {
        $expectedId = 1;
        $expectedUserId = 2;
        $expectedFrom = '2025-12-01';
        $expectedTo = '2025-12-05';
        $expectedReason = 'Family vacation';
        $expectedStatus = VacationStatus::Pending->value;
        $expectedAuthorizedBy = 3;
        $expectedCreatedAt = '2025-11-06 10:00:00';
        $expectedUpdatedAt = '2025-11-06 11:00:00';

        $dbMock = $this->createMock(Database::class);
        $repo = new VacationsRepository($dbMock);

        $dbMock->expects($this->once())
            ->method('query')
            ->with(
                $this->equalTo('SELECT id, user_id, `from`, `to`, reason, status, authorized_by, created_at, updated_at FROM vacations WHERE id = :id'),
                ['id' => $expectedId]
            )
            ->willReturn([
                [
                    'id' => $expectedId,
                    'user_id' => $expectedUserId,
                    'from' => $expectedFrom,
                    'to' => $expectedTo,
                    'reason' => $expectedReason,
                    'status' => $expectedStatus,
                    'authorized_by' => $expectedAuthorizedBy,
                    'created_at' => $expectedCreatedAt,
                    'updated_at' => $expectedUpdatedAt,
                ]
            ]);

        $vacation = $repo->findById($expectedId);

        $this->assertSame($expectedId, $vacation->getId());
        $this->assertSame($expectedUserId, $vacation->getUserId());
        $this->assertSame($expectedFrom, $vacation->getFrom()->format('Y-m-d'));
        $this->assertSame($expectedTo, $vacation->getTo()->format('Y-m-d'));
        $this->assertSame($expectedReason, $vacation->getReason());
        $this->assertSame($expectedStatus, $vacation->getStatus()->value);
        $this->assertSame($expectedAuthorizedBy, $vacation->getAuthorizedBy());
        $this->assertSame($expectedCreatedAt, $vacation->getCreatedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame($expectedUpdatedAt, $vacation->getUpdatedAt()?->format('Y-m-d H:i:s'));
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testFindByIdNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Vacation with ID 1 not found.');

        $dbMock = $this->createMock(Database::class);
        $repo = new VacationsRepository($dbMock);

        $dbMock->expects($this->once())
            ->method('query')
            ->willReturn([]);

        $repo->findById(1);
    }

    public function testDeleteSuccess(): void
    {
        $dbMock = $this->createMock(Database::class);
        $repo = new VacationsRepository($dbMock);

        $dbMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo('DELETE FROM vacations WHERE id = :id AND status = :status'), ['id' => 1, 'status' => VacationStatus::Pending->value])
            ->willReturn(1);

        $repo->delete(1);
    }

    public function testDeleteNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Vacation with ID 1 not found.');

        $dbMock = $this->createMock(Database::class);
        $repo = new VacationsRepository($dbMock);

        $dbMock->expects($this->once())
            ->method('execute')
            ->willReturn(0);

        $repo->delete(1);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testUpdateStatusSuccess(): void
    {
        $vacationId = 100;
        $managerId = 2;
        $newStatus = VacationStatus::Approved;

        $dbMock = $this->createMock(Database::class);
        $repo = new VacationsRepository($dbMock);

        $dbMock->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo('UPDATE vacations SET status = :status, authorized_by = :authorized_by WHERE id = :id AND status = :old_status'),
                [
                    'status' => $newStatus->value,
                    'authorized_by' => $managerId,
                    'id' => $vacationId,
                    'old_status' => VacationStatus::Pending->value,
                ]
            )
            ->willReturn(1);

        $dbMock->expects($this->once())
            ->method('query')
            ->willReturn([
                [
                    'id' => $vacationId,
                    'user_id' => 1,
                    'from' => '2025-12-01',
                    'to' => '2025-12-05',
                    'reason' => 'Family vacation',
                    'status' => $newStatus->value,
                    'authorized_by' => $managerId,
                    'created_at' => '2025-11-06 10:00:00',
                    'updated_at' => '2025-11-06 11:00:00',
                ]
            ]);

        $updatedVacation = $repo->updateStatus($vacationId, $managerId, $newStatus);

        $this->assertSame($newStatus, $updatedVacation->getStatus());
        $this->assertSame($managerId, $updatedVacation->getAuthorizedBy());
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testUpdateStatusNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Vacation with ID 1 not found.');

        $dbMock = $this->createMock(Database::class);
        $repo = new VacationsRepository($dbMock);

        $dbMock->expects($this->once())
            ->method('execute')
            ->willReturn(0);

        $repo->updateStatus(1, 2, VacationStatus::Approved);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testFindPendingRequests(): void
    {
        $dbMock = $this->createMock(Database::class);
        $repo = new VacationsRepository($dbMock);

        $dbMock->expects($this->once())
            ->method('query')
            ->with(
                $this->callback(function (string $sql) {
                    return str_starts_with($sql, 'SELECT v.id, v.user_id, v.`from`, v.`to`, v.reason, v.status, v.authorized_by, v.created_at, v.updated_at,')
                        && str_contains($sql, 'u.name AS employee_name, u.email AS employee_email, u.employee_code, u.role AS employee_role')
                        && str_ends_with($sql, 'FROM vacations v INNER JOIN users u ON v.user_id = u.id WHERE v.status = :status ORDER BY v.`from` ASC');
                })
            )
            ->willReturn([
                [
                    'id' => 1,
                    'user_id' => 10,
                    'from' => '2025-12-01',
                    'to' => '2025-12-05',
                    'reason' => 'Vacation 1',
                    'status' => 'pending',
                    'authorized_by' => null,
                    'created_at' => '2025-11-06 10:00:00',
                    'updated_at' => '2025-11-06 10:00:00',
                    'employee_name' => 'John Doe',
                    'employee_email' => 'john.doe@example.com',
                    'employee_code' => '1234567',
                    'employee_role' => 'employee',
                ],
                [
                    'id' => 2,
                    'user_id' => 11,
                    'from' => '2025-12-10',
                    'to' => '2025-12-15',
                    'reason' => 'Vacation 2',
                    'status' => 'pending',
                    'authorized_by' => 20,
                    'created_at' => '2025-11-07 10:00:00',
                    'updated_at' => '2025-11-09 10:00:00',
                    'employee_name' => 'Jane Smith',
                    'employee_email' => 'jane.smith@example.com',
                    'employee_code' => '7654321',
                    'employee_role' => 'employee',
                ]
            ]);

        $collection = $repo->findPendingRequests();

        /** @var array<int, array<string, mixed>> $vacations */
        $vacations = $collection->jsonSerialize();
        /** @var array<string, mixed> $user1 */
        $user1 = $vacations[0]['user'];
        /** @var array<string, mixed> $user2 */
        $user2 = $vacations[1]['user'];
        $this->assertCount(2, $vacations);
        $this->assertSame('Vacation 1', $vacations[0]['reason']);
        $this->assertSame('John Doe', $user1['name']);
        $this->assertSame('Vacation 2', $vacations[1]['reason']);
        $this->assertSame('Jane Smith', $user2['name']);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testFindUserRequests(): void
    {
        $userId = 1;
        $dbMock = $this->createMock(Database::class);
        $repo = new VacationsRepository($dbMock);

        $dbMock->expects($this->once())
            ->method('query')
            ->with(
                $this->equalTo('SELECT id, user_id, `from`, `to`, reason, status, authorized_by, created_at, updated_at FROM vacations WHERE user_id = :user_id ORDER BY `from` DESC'),
                ['user_id' => $userId]
            )
            ->willReturn([
                [
                    'id' => 10,
                    'user_id' => $userId,
                    'from' => '2025-12-01',
                    'to' => '2025-12-05',
                    'reason' => 'Vacation 1',
                    'status' => 'pending',
                    'authorized_by' => null,
                    'created_at' => '2025-11-06 10:00:00',
                    'updated_at' => '2025-11-06 10:00:00',
                ],
            ]);

        $collection = $repo->findUserRequests($userId);
        /** @var array<int, array<string, mixed>> $vacations */
        $vacations = $collection->jsonSerialize();
        $this->assertCount(1, $vacations);
        $this->assertSame($userId, $vacations[0]['userId']);
    }
}
