<?php

declare(strict_types=1);

namespace App\Domains\Vacations\Repositories;

use App\Domains\Users\Models\UserModel;
use App\Domains\Users\Repositories\UsersRepository;
use App\Domains\Vacations\Models\VacationModel;
use App\Domains\Vacations\Models\VacationStatus;
use App\Shared\Database;
use App\Shared\ModelCollection;
use DateMalformedStringException;
use DateTime;
use InvalidArgumentException;

readonly class VacationsRepository
{
    public function __construct(private Database $db)
    {
    }

    /**
     * @throws DateMalformedStringException
     */
    public function findPendingRequests(): ModelCollection
    {
        $sql = "SELECT v.id, v.user_id, v.`from`, v.`to`, v.reason, v.status, v.authorized_by, v.created_at, v.updated_at,
            u.name AS employee_name, u.email AS employee_email, u.employee_code, u.role AS employee_role
            FROM vacations v INNER JOIN users u ON v.user_id = u.id WHERE v.status = :status ORDER BY v.`from` ASC";

        $rows = $this->db->query($sql, ['status' => VacationStatus::Pending->value]);

        $vacations = new ModelCollection();

        foreach ($rows as $data) {
            $user = UsersRepository::mapToUserModel([
                'id' => $data['user_id'],
                'name' => $data['employee_name'],
                'email' => $data['employee_email'],
                'employee_code' => $data['employee_code'],
                'role' => $data['employee_role'],
            ]);
            $vacation = self::mapToVacationModel($data, $user);
            $vacations->add($vacation);
        }

        return $vacations;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function findUserRequests(int $userId): ModelCollection
    {
        $sql = "SELECT id, user_id, `from`, `to`, reason, status, authorized_by, created_at, updated_at FROM vacations WHERE user_id = :user_id ORDER BY `from` DESC";

        $rows = $this->db->query($sql, ['user_id' => $userId]);

        $vacations = new ModelCollection();

        foreach ($rows as $data) {
            $vacation = self::mapToVacationModel($data);
            $vacations->add($vacation);
        }

        return $vacations;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function findById(int $id): VacationModel
    {
        $sql = "SELECT id, user_id, `from`, `to`, reason, status, authorized_by, created_at, updated_at FROM vacations WHERE id = :id";

        $row = $this->db->query($sql, ['id' => $id]);

        if (empty($row)) {
            throw new InvalidArgumentException("Vacation with ID $id not found.");
        }

        return $this->mapToVacationModel($row[0]);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function updateStatus(int $id, int $managerId, VacationStatus $status): VacationModel
    {
        $sql = "UPDATE vacations SET status = :status, authorized_by = :authorized_by WHERE id = :id AND status = :old_status";
        $rowCount = $this->db->execute($sql, [
            'status' => $status->value,
            'authorized_by' => $managerId,
            'id' => $id,
            'old_status' => VacationStatus::Pending->value
        ]);

        if ($rowCount === 0) {
            throw new InvalidArgumentException("Vacation with ID $id not found.");
        }

        return $this->findById($id);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function create(VacationModel $vacationModel): VacationModel
    {
        $sql = "INSERT INTO vacations (user_id, `from`, `to`, reason, status)
                VALUES (:user_id, :from, :to, :reason, :status)";

        $this->db->execute($sql, [
            'user_id' => $vacationModel->getUserId(),
            'from' => $vacationModel->getFrom()->format('Y-m-d'),
            'to' => $vacationModel->getTo()->format('Y-m-d'),
            'reason' => $vacationModel->getReason(),
            'status' => $vacationModel->getStatus()->value,
        ]);

        $id = (int) $this->db->lastInsertId();

        // Fetch created_at and updated_at from DB
        $row = $this->db->query(
            "SELECT created_at, updated_at FROM vacations WHERE id = :id",
            ['id' => $id]
        )[0];

        $createdAt = isset($row['created_at']) && is_string($row['created_at']) ? new DateTime($row['created_at']) : null;
        $updatedAt = isset($row['updated_at']) && is_string($row['updated_at']) ? new DateTime($row['updated_at']) : null;

        return new VacationModel(
            $id,
            $vacationModel->getUserId(),
            $vacationModel->getUser(),
            $vacationModel->getFrom(),
            $vacationModel->getTo(),
            $vacationModel->getReason(),
            $vacationModel->getStatus(),
            $vacationModel->getAuthorizedBy(),
            $createdAt,
            $updatedAt
        );
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM vacations WHERE id = :id AND status = :status";
        $rowCount = $this->db->execute($sql, ['id' => $id, 'status' => VacationStatus::Pending->value]);

        if ($rowCount === 0) {
            throw new InvalidArgumentException("Vacation with ID $id not found.");
        }
    }

    /**
     * @param array<string, mixed> $data
     * @throws DateMalformedStringException
     */
    public static function mapToVacationModel(array $data, ?UserModel $user = null): VacationModel
    {
        $id = (isset($data['id']) && is_scalar($data['id'])) ? intval($data['id']) : null;
        $userId = (isset($data['user_id']) && is_scalar($data['user_id'])) ? intval($data['user_id']) : null;
        $from = isset($data['from']) && is_string($data['from']) ? new DateTime($data['from']) : null;
        $to = isset($data['to']) && is_string($data['to']) ? new DateTime($data['to']) : null;
        $reason = isset($data['reason']) && is_string($data['reason']) ? $data['reason'] : '';
        $status = isset($data['status']) && is_string($data['status']) ? VacationStatus::from($data['status']) : VacationStatus::Pending;
        $authorizedBy = (isset($data['authorized_by']) && is_scalar($data['authorized_by'])) ? intval($data['authorized_by']) : null;
        $createdAt = isset($data['created_at']) && is_string($data['created_at']) ? new DateTime($data['created_at']) : null;
        $updatedAt = isset($data['updated_at']) && is_string($data['updated_at']) ? new DateTime($data['updated_at']) : null;

        assert(!is_null($userId));
        assert(!is_null($from));
        assert(!is_null($to));

        return new VacationModel(
            $id,
            $userId,
            $user,
            $from,
            $to,
            $reason,
            $status,
            $authorizedBy,
            $createdAt,
            $updatedAt
        );
    }
}
