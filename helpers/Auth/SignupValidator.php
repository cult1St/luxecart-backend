<?php

namespace Helpers\Auth;

/**
 * Signup Validator
 * 
 * Validates user registration data
 */
class SignupValidator
{
    /**
     * Validate signup data
     * 
     * @param array $data Input data
     * @param bool $validatePassword Whether to validate password (false for Google signup)
     * @return array Array with 'valid' => bool and 'errors' => array
     */
    public static function validate(array $data, bool $validatePassword = true): array
    {
        $errors = [];

        // Name validation
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        } elseif (strlen($data['name']) > 100) {
            $errors['name'] = 'Name must not exceed 100 characters';
        }

        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } elseif (strlen($data['email']) > 150) {
            $errors['email'] = 'Email must not exceed 150 characters';
        }

        // Password validation (if needed)
        if ($validatePassword) {
            if (empty($data['password'])) {
                $errors['password'] = 'Password is required';
            } elseif (strlen($data['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            } elseif (strlen($data['password']) > 255) {
                $errors['password'] = 'Password must not exceed 255 characters';
            }

            // Confirm password validation
            if (empty($data['confirm_password'])) {
                $errors['confirm_password'] = 'Please confirm your password';
            } elseif ($data['password'] !== $data['confirm_password']) {
                $errors['confirm_password'] = 'Passwords do not match';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate verification code
     * 
     * @param array $data Input data containing email and code
     * @return array Array with 'valid' => bool and 'errors' => array
     */
    public static function validateVerification(array $data): array
    {
        $errors = [];

        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }

        // Code validation
        if (empty($data['code'])) {
            $errors['code'] = 'Verification code is required';
        } elseif (!preg_match('/^\d{6}$/', $data['code'])) {
            $errors['code'] = 'Verification code must be exactly 6 digits';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Sanitize signup data
     * 
     * @param array $data Input data
     * @return array Sanitized data
     */
    public static function sanitize(array $data): array
    {
        return [
            'name' => isset($data['name']) ? trim($data['name']) : '',
            'email' => isset($data['email']) ? strtolower(trim($data['email'])) : '',
            'password' => $data['password'] ?? '',
            'confirm_password' => $data['confirm_password'] ?? '',
            'google_id' => isset($data['google_id']) ? trim($data['google_id']) : null,
        ];
    }

    /**
     * Sanitize verification data
     * 
     * @param array $data Input data
     * @return array Sanitized data
     */
    public static function sanitizeVerification(array $data): array
    {
        return [
            'email' => isset($data['email']) ? strtolower(trim($data['email'])) : '',
            'code' => isset($data['code']) ? trim($data['code']) : '',
        ];
    }
}
