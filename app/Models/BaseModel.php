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
    protected bool $useObjects = true;  // Use stdClass by default

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Convert array to stdClass object
     */
    protected function toObject(?array $data): ?object
    {
        if ($data === null) {
            return null;
        }
        return json_decode(json_encode($data), false);
    }

    /**
     * Convert array of items to stdClass objects
     */
    protected function toObjectArray(array $data): array
    {
        return array_map(fn($item) => $this->toObject($item), $data);
    }

    /**
     * Set whether to use objects or arrays
     */
    public function useObjects(bool $use = true): self
    {
        $this->useObjects = $use;
        return $this;
    }

    /**
     * Get all records
     */
    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $results = $this->db->fetchAll($sql);
        return $this->useObjects ? $this->toObjectArray($results) : $results;
    }

    /**
     * Find record by ID
     */
    public function find(int $id): ?object
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $result = $this->db->fetch($sql, [$id]);
        if ($result === null) {
            return null;
        }
        return $this->useObjects ? $this->toObject($result) : $result;
    }

    /**
     * Find by column
     */
    public function findBy(string $column, $value): ?object
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ?";
        $result = $this->db->fetch($sql, [$value]);
        if ($result === null) {
            return null;
        }
        return $this->useObjects ? $this->toObject($result) : $result;
    }

    /**
     * Get multiple records by column
     */
    public function where(string $column, $value): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ?";
        $results = $this->db->fetchAll($sql, [$value]);
        return $this->useObjects ? $this->toObjectArray($results) : $results;
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
