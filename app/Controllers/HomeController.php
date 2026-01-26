<?php

namespace App\Controllers;

/**
 * Home Controller
 * 
 * Handles home page and landing page
 */
class HomeController extends BaseController
{
    /**
     * Show home page - returns JSON for React frontend
     */
    public function index(): void
    {
        $this->response->success(
            [
                'message' => 'Welcome to Frisan API',
                'endpoints' => [
                    'signup' => 'POST /api/auth/signup',
                    'verify_email' => 'POST /api/auth/verify-email',
                    'login' => 'POST /api/auth/login',
                    'logout' => 'POST /api/auth/logout',
                    'resend_code' => 'POST /api/auth/resend-code',
                    'google_auth' => 'POST /api/auth/google',
                ],
                'status' => 'API running'
            ],
            'Frisan Authentication API Ready',
            200
        );
    }

    
}

