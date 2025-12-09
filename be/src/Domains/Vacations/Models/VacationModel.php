<?php

declare(strict_types=1);

namespace App\Domains\Vacations\Models;

use App\Domains\Users\Models\UserModel;
use DateMalformedStringException;
use DateTime;
use InvalidArgumentException;
use JsonSerializable;

readonly class VacationModel implements JsonSerializable
{
    public const int MAX_VACATION_DAYS = 60;
    private ?int $id;
    private int $userId;
    private ?UserModel $user;
    private DateTime $from;
    private DateTime $to;
    private string $reason;
    private VacationStatus $status;
    private ?int $authorizedBy;
    private ?DateTime $createdAt;
    private ?DateTime $updatedAt;

    /**
     * @throws DateMalformedStringException
     */
    public function __construct(
        ?int $id,
        int $userId,
        ?UserModel $user,
        DateTime $from,
        DateTime $to,
        string $reason,
        VacationStatus $status = VacationStatus::Pending,
        ?int $authorizedBy = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        if (empty(trim($reason))) {
            throw new InvalidArgumentException('Reason must not be empty.');
        }

        if ($from > $to) {
            throw new InvalidArgumentException('The "from" date must be earlier than the "to" date.');
        }

        $interval = $from->diff($to);
        if ($interval->days > self::MAX_VACATION_DAYS) {
            throw new InvalidArgumentException('Vacation duration cannot be more than 60 days.');
        }

        $this->id = $id;
        $this->userId = $userId;
        $this->user = $user;
        $this->from = $from;
        $this->to = $to;
        $this->reason = $reason;
        $this->status = $status;
        $this->authorizedBy = $authorizedBy;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUser(): ?UserModel
    {
        return $this->user;
    }

    public function getFrom(): DateTime
    {
        return $this->from;
    }

    public function getTo(): DateTime
    {
        return $this->to;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getStatus(): VacationStatus
    {
        return $this->status;
    }

    public function getAuthorizedBy(): ?int
    {
        return $this->authorizedBy;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'user' => $this->user?->toArray(),
            'from' => $this->from->format('Y-m-d'),
            'to' => $this->to->format('Y-m-d'),
            'reason' => $this->reason,
            'status' => $this->status->value,
            'authorizedBy' => $this->authorizedBy,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
