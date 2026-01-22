<?php

namespace App\Controllers;

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
        
    }
}
