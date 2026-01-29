<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use Core\Database;
use Core\Request;
use Core\Response;
use Helpers\ErrorResponse;
use App\Models\User;
use App\Models\Customer;
use Throwable;

/**
 * Account Controller
 * 
 * Handles user account management, profile updates, and settings
 */
class AccountController extends BaseController
{
    protected User $userModel;
    protected Customer $customerModel;

    public function __construct(Database $db, Request $request, Response $response)
    {
        parent::__construct($db, $request, $response);
        $this->userModel = new User($db);
        $this->customerModel = new Customer($db);
    }

    /**
     * Get account info - GET /account
     * 
     * Returns: User profile data
     */
    public function index(): void
    {
        try {
            // Check authentication
            if (!$this->isAuthenticated()) {
                $this->response->error(ClientLang::UNAUTHORIZED, [], 401);
                return;
            }

            $userId = $this->getUserId();
            if (!$userId) {
                $this->response->error(ClientLang::USER_NOT_FOUND, [], 404);
                return;
            }

            // Get user data
            $user = $this->userModel->find($userId);
            if (!$user) {
                $this->response->error(ClientLang::USER_NOT_FOUND, [], 404);
                return;
            }

            // Get customer address if exists
            $address = $this->customerModel->getAddress($userId);

            $this->response->success(
                [
                    'user_id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'is_verified' => (bool)$user['is_verified'],
                    'is_active' => (bool)$user['is_active'],
                    'address' => $address,
                    'created_at' => $user['created_at'],
                    'updated_at' => $user['updated_at']
                ],
                'Account information retrieved successfully',
                200
            );

        } catch (Throwable $e) {
            $this->log("Account info error: " . $e->getMessage(), 'error');
            $this->response->error(
                'An error occurred while retrieving account information',
                [],
                500
            );
        }
    }

    /**
     * View orders - GET /account/orders
     */
    public function orders(): void
    {
        try {
            if (!$this->isAuthenticated()) {
                $this->response->error(ClientLang::UNAUTHORIZED, [], 401);
                return;
            }

            $userId = $this->authUser['id'] ?? null;
            
            $this->response->success(
                ['orders' => []],
                'Orders retrieved successfully',
                200
            );

        } catch (Throwable $e) {
            $this->log("Orders error: " . $e->getMessage(), 'error');
            $this->response->error(
                'An error occurred while retrieving orders',
                [],
                500
            );
        }
    }

    /**
     * View address - GET /account/address
     */
    public function address(): void
    {
        try {
            if (!$this->isAuthenticated()) {
                $this->response->error(ClientLang::UNAUTHORIZED, [], 401);
                return;
            }

            $userId = $this->authUser['id'] ?? null;
            $address = $this->customerModel->getAddress($userId);

            $this->response->success(
                ['address' => $address],
                'Address retrieved successfully',
                200
            );

        } catch (Throwable $e) {
            $this->log("Address error: " . $e->getMessage(), 'error');
            $this->response->error(
                'An error occurred while retrieving address',
                [],
                500
            );
        }
    }

    /**
     * Update account - POST /account/update
     * 
     * Accepts: name, phone, address, city_id, state_id, country_id, password (old), password_new, password_confirm
     */
    public function update(): void
    {
        try {
            // Check authentication
            if (!$this->isAuthenticated()) {
                $this->response->error(ClientLang::UNAUTHORIZED, [], 401);
                return;
            }

            // Only accept POST requests
            if (!$this->request->isPost()) {
                $this->response->error('Only POST requests are allowed', [], 405);
                return;
            }

            $userId = $this->authUser['id'] ?? null;
            if (!$userId) {
                $this->response->error('User not found', [], 404);
                return;
            }

            // Get input data
            $input = $this->request->all();
            $updateData = [];
            $errors = [];

            // Get current user data
            $user = $this->userModel->find($userId);
            if (!$user) {
                $this->response->error('User not found', [], 404);
                return;
            }

            // Update name if provided
            if (isset($input['name']) && !empty($input['name'])) {
                $name = trim($input['name']);
                if (strlen($name) < 2) {
                    $errors['name'] = 'Name must be at least 2 characters';
                } elseif (strlen($name) > 100) {
                    $errors['name'] = 'Name cannot exceed 100 characters';
                } else {
                    $updateData['name'] = $name;
                }
            }

            // Update phone if provided
            if (isset($input['phone']) && !empty($input['phone'])) {
                $phone = trim($input['phone']);
                if (!preg_match('/^[\d\s\-\+\(\)\.]+$/', $phone)) {
                    $errors['phone'] = 'Invalid phone number format';
                } else {
                    $updateData['phone'] = $phone;
                }
            }

            // Handle password change if requested
            if (isset($input['password']) || isset($input['password_new'])) {
                if (empty($input['password'])) {
                    $errors['password'] = 'Current password is required to change password';
                } elseif (empty($input['password_new'])) {
                    $errors['password_new'] = 'New password is required';
                } elseif (empty($input['password_confirm'])) {
                    $errors['password_confirm'] = 'Password confirmation is required';
                } else {
                    // Verify current password
                    if (!$this->userModel->verifyPassword($input['password'], $user['password'])) {
                        $errors['password'] = 'Current password is incorrect';
                    } elseif (strlen($input['password_new']) < 8) {
                        $errors['password_new'] = 'New password must be at least 8 characters';
                    } elseif ($input['password_new'] !== $input['password_confirm']) {
                        $errors['password_confirm'] = 'Passwords do not match';
                    } else {
                        // Hash new password
                        $updateData['password'] = password_hash($input['password_new'], PASSWORD_BCRYPT);
                    }
                }
            }

            // If there are validation errors, return them
            if (!empty($errors)) {
                $this->response->error('Validation failed', $errors, 422);
                return;
            }

            // Update address if provided
            if (isset($input['address']) || isset($input['city_id']) || isset($input['state_id']) || isset($input['country_id'])) {
                $addressData = [
                    'address' => $input['address'] ?? null,
                    'city_id' => $input['city_id'] ?? null,
                    'state_id' => $input['state_id'] ?? null,
                    'country_id' => $input['country_id'] ?? null,
                ];

                // Validate address data
                if (empty($addressData['address'])) {
                    $errors['address'] = 'Address is required';
                }
                if (empty($addressData['city_id'])) {
                    $errors['city_id'] = 'City is required';
                }
                if (empty($addressData['state_id'])) {
                    $errors['state_id'] = 'State is required';
                }
                if (empty($addressData['country_id'])) {
                    $errors['country_id'] = 'Country is required';
                }

                if (!empty($errors)) {
                    $this->response->error('Validation failed', $errors, 422);
                    return;
                }

                // Update or create address
                $addressData['user_id'] = $userId;
                $this->customerModel->updateAddress($userId, $addressData);
            }

            // Update user data if there's any data to update
            if (!empty($updateData)) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
                $this->userModel->update($userId, $updateData);
            }

            // Get updated user data
            $updatedUser = $this->userModel->find($userId);
            $address = $this->customerModel->getAddress($userId);

            // Log activity
            $this->log("Account updated: {$updatedUser['email']} (ID: $userId)", 'info');

            $this->response->success(
                [
                    'user_id' => $updatedUser['id'],
                    'name' => $updatedUser['name'],
                    'email' => $updatedUser['email'],
                    'phone' => $updatedUser['phone'],
                    'address' => $address
                ],
                'Account updated successfully',
                200
            );

        } catch (Throwable $e) {
            $this->log("Account update error: " . $e->getMessage(), 'error');
            $errorMessage = ErrorResponse::formatResponse($e);
            $this->response->error(
                $errorMessage,
                [],
                500
            );
        }
    }
}
