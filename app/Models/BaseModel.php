<?php

namespace App\Models;

use Core\Database;

abstract class BaseModel
{
    protected Database $db;
    protected string $table;
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected bool $useObjects = true;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /* ----------------------------------------------------
     |  Object helpers
     | ---------------------------------------------------- */

    protected function toObject(?array $data): ?object
    {
        if ($data === null) return null;

        $obj = json_decode(json_encode($data), false);
        return $this->castAttributes($obj);
    }

    protected function toObjectArray(array $data): array
    {
        return array_map(fn($item) => $this->castAttributes($this->toObject($item)), $data);
    }


    public function useObjects(bool $use = true): self
    {
        $this->useObjects = $use;
        return $this;
    }

    /* ----------------------------------------------------
     |  Basic queries
     | ---------------------------------------------------- */

    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $results = $this->db->fetchAll($sql);
        return $this->useObjects ? $this->toObjectArray($results) : $results;
    }

    public function find(int $id): ?object
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $result = $this->db->fetch($sql, [$id]);

        return $result
            ? ($this->useObjects ? $this->toObject($result) : $result)
            : null;
    }

    /**
     * Find single record by column
     */
    public function findBy(string $column, $value): ?object
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ? LIMIT 1";
        $result = $this->db->fetch($sql, [$value]);

        return $result
            ? ($this->useObjects ? $this->toObject($result) : $result)
            : null;
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
     * Check if record exists by ID
     */
    public function exists(int $id): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->db->fetch($sql, [$id]) !== null;
    }

    /* ----------------------------------------------------
     |  Pagination
     | ---------------------------------------------------- */

    public function paginate(
        int $page = 1,
        int $perPage = 15,
        array $options = []
    ): array {
        $page = max($page, 1);
        $perPage = max($perPage, 1);
        $offset = ($page - 1) * $perPage;

        $whereSql = '';
        $params = [];

        // WHERE
        if (!empty($options['where']) && is_array($options['where'])) {
            $conditions = [];
            foreach ($options['where'] as $column => $value) {
                $conditions[] = "{$column} = ?";
                $params[] = $value;
            }
            $whereSql = 'WHERE ' . implode(' AND ', $conditions);
        }

        // ORDER BY
        $orderBy = $options['orderBy'] ?? 'id';
        $direction = strtoupper($options['direction'] ?? 'DESC');
        $direction = in_array($direction, ['ASC', 'DESC']) ? $direction : 'DESC';

        // TOTAL COUNT
        $countSql = "SELECT COUNT(*) AS total FROM {$this->table} {$whereSql}";
        $total = (int) ($this->db->fetch($countSql, $params)['total'] ?? 0);

        // DATA
        $sql = "
            SELECT * FROM {$this->table}
            {$whereSql}
            ORDER BY {$orderBy} {$direction}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $data = $this->db->fetchAll($sql, $params);
        $data = $this->useObjects ? $this->toObjectArray($data) : $data;

        return [
            'data' => $data,
            'meta' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ];
    }

    /* ----------------------------------------------------
     |  Write operations
     | ---------------------------------------------------- */

    public function create(array $data): int
    {
        return $this->db->insert(
            $this->table,
            $this->filterFillable($data)
        );
    }

    public function update(int $id, array $data): int
    {
        return $this->db->update(
            $this->table,
            $this->filterFillable($data),
            "id = {$id}"
        );
    }

    public function delete(int $id): int
    {
        return $this->db->delete($this->table, "id = {$id}");
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) AS count FROM {$this->table}";
        return (int) ($this->db->fetch($sql)['count'] ?? 0);
    }

    /**
     * Get last inserted ID
     */
    public function getLastId(): int
    {
        return (int) $this->db->getPdo()->lastInsertId();
    }

    /* ----------------------------------------------------
     |  Helpers
     | ---------------------------------------------------- */

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

    public function getTableName(): string
    {
        return $this->table;
    }

    /**
     * Cast attributes based on $casts property
     * Automatically resolves things like JSON, dates, integers, booleans
     */
    protected function castAttributes(array|object $data): array|object
    {
        if (empty($this->casts)) {
            return $data;
        }

        foreach ($this->casts as $attribute => $type) {
            if (is_object($data)) {
                if (!property_exists($data, $attribute)) continue;
                $value = $data->{$attribute};
                $data->{$attribute} = $this->castValue($value, $type);
            } else {
                if (!array_key_exists($attribute, $data)) continue;
                $data[$attribute] = $this->castValue($data[$attribute], $type);
            }
        }

        return $data;
    }

    /**
     * Cast single value to the specified type
     */
    protected function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'json' => is_string($value) ? json_decode($value, true) : (array) $value,
            'int'  => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            'string' => (string) $value,
            default => $value,
        };
    }
}
