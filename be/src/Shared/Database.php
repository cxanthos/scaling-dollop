<?php

declare(strict_types=1);

namespace App\Shared;

use PDO;
use PDOException;

class Database
{
    private PDO $pdo;

    /**
     * @param array<string, string> $config
     */
    public function __construct(array $config)
    {
        $dsn = $config['driver'] . ":host=" . $config['host'] . ";dbname=" . $config['database'] . ";charset=" . $config['charset'];
        try {
            $this->pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
            );
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        /** @var array<int, array<string, mixed>>|false $result */
        $result = $stmt->fetchAll();

        return $result === false ? [] : $result;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }
}
