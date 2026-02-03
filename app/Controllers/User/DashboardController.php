<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Order;
use App\Models\User;
use Core\Database;
use Core\Request;
use Core\Response;

/**
 * Dashboard Controller
 * 
 * Handles dashboard and stats page
 */
class DashboardController extends BaseController
{
    private $userModel;

    public function __construct(Database $db, Request $request, Response $response)
    {
        parent::__construct($db, $request, $response);
        $this->userModel = new User($db);
    }

    /**
     * get Dashboard stats
     */
    public function index()
    {


        $orderModel = new Order($this->db);
        //protect route
        $this->requireAuth();

        //get logged in userr
        $userId = $this->getUserId();

        //get user details
        $user = $this->userModel->find($userId);
        if (!$user || empty($user)) {
            $this->response->error('User not found', 404);
        }

        //get user stats
        $ordersSummary = $orderModel->getSummaryByUsers($user->id);

        //correct orders sumamry keys if null
        $ordersSummary = array_map(function ($value) {
            return $value === null ? 0 : $value;
        }, $ordersSummary);

        $this->response->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_verified' => $user->is_verified,
            ],
            'orders_summary' => $ordersSummary
        ], 'Dashboard stats retrieved successfully');
    }
}
