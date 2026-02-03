<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Admin;
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
    private $adminModel;
    private $userModel;

    public function __construct(Database $db, Request $request, Response $response)
    {
        parent::__construct($db, $request, $response);
        $this->adminModel = new Admin($db);
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
        $adminId = $this->getUserId();

        //get admin details
        $admin = $this->adminModel->find($adminId);
        if (!$admin || empty($admin)) {
            $this->response->error('Admin not found', 404);
        }

        //get admin stats
        $recentOrdersCount = $orderModel->getRecentOrdersCount();
        $totalRevenue = $orderModel->getTotalSalesAmount();
        $totalUsers = $this->userModel->getAllUsersCount();
        $recentOrders = $orderModel->getLatestOrders(15);

        $ordersSummary = [
            'recent_orders_count' => $recentOrdersCount,
            'total_revenue' => $totalRevenue,
            'total_users'   => $totalUsers,
            'recent_orders' => $recentOrders
        ];

        $this->response->success([
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'is_verified' => $admin->is_verified,
            ],
            'orders_summary' => $ordersSummary
        ], 'Dashboard stats retrieved successfully');
    }
}
