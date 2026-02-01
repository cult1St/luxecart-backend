<?php 
namespace Helpers\Admin;

use Helpers\Validator;

/**
 * Validator class for products 
 */

class ProductValidator extends Validator{
    public static function validate(array $data): array{
        $filters = [
            "product_name" => [
                "sanitizations" => "string",
                "validations" => "required|maxlen:255"
            ],
            "product_description" => [
                "sanitizations" => "string",
                "validations" => "required"
            ],
            "product_price" => [
                "sanitizations" => "float",
                "validations" => "required|numeric|minlen:0"
            ],
            "stock_quantity" => [
                "sanitizations" => "numeric",
                "validations" => "required|integer|minlen:0"
            ],
            "product_images" => [
                "sanitizations" => "array"
            ]
        ];
        $validator = new parent($filters);
        $validate = $validator->run($data);
        return [
            "sanitizeData" => $validate,
            "errors" => $validator->getValidationErrors(),

        ];
    }
}