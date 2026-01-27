<?php

namespace App\Models;

class ShippingInfo extends BaseModel
{
    protected string $table = 'shipping_infos';

    /**
     * Create new shipping info
     */
    public function create(array $data): int
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * get shipping info by cart
     */
    public function getByCart(int $cartId): ?array
    {
        $result = $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE cart_id = ?",
            [$cartId]
        );

        return $result ?: null;
    }

    public function getColumns(): array
    {  // removed shipping amount from accepted columns so it is only updated when method is uodated
        return [
            'first_name',
            'last_name',
            'company_name',
            'address',
            'country_id',
            'state_id',
            'city_id',
            'zip_code',
            'email',
            'phone_number',
            'notes',
            'shipping_method'
        ];
    }

    /**
     * Update shipping info by cart ID (partial)
     */
    public function updateByCart(int $cartId, array $data): int
    {
        $set = [];
        $params = [];

        foreach ($data as $column => $value) {
            $set[] = "{$column} = ?";
            $params[] = $value;
        }

        $params[] = $cartId;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE cart_id = ?";

        return $this->db->query($sql, $params)->rowCount();
    }
}
