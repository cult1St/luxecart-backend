<?php

namespace App\Models;

use Core\Database;

/**
 * Base Model
 * 
 * Parent class for all models with common CRUD operations
 */
abstract class BaseModel
{
    protected Database $db;
    protected string $table;
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get all records
     */
    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        return $this->db->fetchAll($sql);
    }

    /**
     * Find record by ID
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $result = $this->db->fetch($sql, [$id]);
        return $result === false ? null : $result;
    }

    /**
     * Find by column
     */
    public function findBy(string $column, $value): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ?";
        $result = $this->db->fetch($sql, [$value]);
        return $result === false ? null : $result;
    }

    /**
     * Get multiple records by column
     */
    public function where(string $column, $value): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ?";
        return $this->db->fetchAll($sql, [$value]);
    }

    /**
     * Create record
     */
    public function create(array $data): int
    {
        $filteredData = $this->filterFillable($data);
        return $this->db->insert($this->table, $filteredData);
    }

    /**
     * Update record
     */
    public function update(int $id, array $data): int
    {
        $filteredData = $this->filterFillable($data);
        return $this->db->update($this->table, $filteredData, "id = {$id}");
    }

    /**
     * Delete record
     */
    public function delete(int $id): int
    {
        return $this->db->delete($this->table, "id = {$id}");
    }

    /**
     * Count records
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->fetch($sql);
        return $result['count'] ?? 0;
    }

    /**
     * Check if exists
     */
    public function exists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * Get last inserted ID
     */
    public function getLastId(): int
    {
        return (int)$this->db->getPdo()->lastInsertId();
    }

    /**
     * Filter data by fillable attributes
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_filter(
            $data,
            fn($key) => in_array($key, $this->fillable),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Get table name
     */
    public function getTableName(): string
    {
        return $this->table;
    }
}
