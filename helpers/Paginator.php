<?php

namespace Helpers;

/**
 * Pagination Helper
 * 
 * Handles pagination logic
 */
class Paginator
{
    protected int $page;
    protected int $perPage;
    protected int $total;
    protected array $items;

    public function __construct(array $items, int $page = 1, int $perPage = 15)
    {
        $this->items = $items;
        $this->page = max(1, $page);
        $this->perPage = max(1, $perPage);
        $this->total = count($items);
    }

    /**
     * Get paginated items
     */
    public function getItems(): array
    {
        $start = ($this->page - 1) * $this->perPage;
        return array_slice($this->items, $start, $this->perPage);
    }

    /**
     * Get total pages
     */
    public function getTotalPages(): int
    {
        return (int)ceil($this->total / $this->perPage);
    }

    /**
     * Get current page
     */
    public function getCurrentPage(): int
    {
        return $this->page;
    }

    /**
     * Check if has previous page
     */
    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    /**
     * Check if has next page
     */
    public function hasNextPage(): bool
    {
        return $this->page < $this->getTotalPages();
    }

    /**
     * Get previous page number
     */
    public function getPreviousPage(): int
    {
        return max(1, $this->page - 1);
    }

    /**
     * Get next page number
     */
    public function getNextPage(): int
    {
        return min($this->getTotalPages(), $this->page + 1);
    }

    /**
     * Get total items
     */
    public function getTotal(): int
    {
        return $this->total;
    }
}
