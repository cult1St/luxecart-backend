<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Order;
use App\Models\User;

/**
 * Dashboard Controller
 * 
 * Handles dashboard and stats page
 */
class DashboardController extends BaseController
{

   /**
    * get Dashboard stats
    */
    public function index(){
        //protect route
        $this->requireAuth();

        //get logged in userr
        $userId = $this->getUserId();

        //get user details
        $userModel = new User($this->db);
        $user = $userModel->find($userId);
        if(!$user || empty($user)){
            $this->response->error('User not found', [], 404);
        }

        //get user stats
        $orderModel = new Order($this->db);
        $ordersSummary = $orderModel->getSummaryByUsers($user['id']);

        //correct orders sumamry keys if null
        $ordersSummary = array_map(function($value){
            return $value === null ? 0 : $value;
        }, $ordersSummary);

        $this->response->success([
            'user' => $user,
            'orders_summary' => $ordersSummary
        ], 'Dashboard stats retrieved successfully');
    }
}
