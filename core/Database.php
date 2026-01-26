<?php

namespace Core;

use PDO;
use PDOException;

/**
 * Database Connection Handler
 * 
 * Manages database connections and provides query building utilities
 */
class Database
{
    protected PDO $pdo;
    protected $lastQuery;
    protected $lastError;

    public function __construct(array $config)
    {
        //die(var_dump($config));
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";

            $this->pdo = new PDO(
                $dsn,
                $config['user'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Execute a prepared statement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $this->lastQuery = $sql;
            $statement = $this->pdo->prepare($sql);
            $statement->execute($params);
            return $statement;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            throw new \Exception("Query failed: " . $e->getMessage());
        }
    }

    /**
     * Fetch all results
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch single result
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch(\PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    /**
     * Insert record
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $this->query($sql, array_values($data));
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update record
     */
    public function update(string $table, array $data, string $where): int
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";

        $statement = $this->query($sql, array_values($data));
        return $statement->rowCount();
    }

    /**
     * Delete record
     */
    public function delete(string $table, string $where): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $statement = $this->query($sql);
        return $statement->rowCount();
    }

    /**
     * Get PDO instance
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Get last error
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Begin a database transaction
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit the current transaction
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Roll back the current transaction
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Check if currently in a transaction
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
}
