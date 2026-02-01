<?php 
namespace Helpers\Auth;

/**
 * Create Password Validator
 * 
 * Validates user input for creating a new password
 */

class CreatePasswordValidator
{
    /**
     * Validate create password input
     * 
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Password validation
        if (empty($data['password']) || !isset($data['password']) || !isset($data['confirm_password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        } elseif (strlen($data['password']) > 255) {
            $errors['password'] = 'Password is too long';
        }elseif ($data['password'] !== $data['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Sanitize create password input
     */
    public static function sanitize(array $data): array
    {
        return [
            'password' => isset($data['password']) ? $data['password'] : '',
            'confirm_password' => isset($data['confirm_password']) ? $data['confirm_password'] : ''
        ];
    }
}