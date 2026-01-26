<?php

namespace App\Helpers;

/**
 * Login Validator
 * 
 * Validates user login input data
 */
class LoginValidator
{
    /**
     * Validate login input
     * 
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email format is invalid';
        } elseif (strlen($data['email']) > 255) {
            $errors['email'] = 'Email cannot exceed 255 characters';
        }

        // Password validation
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        } elseif (strlen($data['password']) > 255) {
            $errors['password'] = 'Password is too long';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Sanitize login input
     * 
     * @param array $data
     * @return array
     */
    public static function sanitize(array $data): array
    {
        return [
            'email' => isset($data['email']) ? trim(strtolower($data['email'])) : '',
            'password' => isset($data['password']) ? $data['password'] : ''
        ];
    }
}
