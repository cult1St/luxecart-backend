<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\User;

/**
 * Auth Controller
 * 
 * Handles authentication operations from login/signup to logout and forgot password
 */
class AuthController extends BaseController
{

    /**
     * Handle forgot password request
     */
    public function forgotPassword()
    {
        if (!$this->request->isPost()) {
            $this->response->error('Invalid request', [], 400);
        }

        $email = $this->request->post('email');
        //validate email
        if(!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)){
            $this->response->error('Valid email is required', [], 400);
        }

        $userModel = new User($this->db);
        $user = $userModel->findBy('email', $email);

        if (!$user) {
            // To prevent email enumeration, respond with success even if user not found
            $this->response->success([], 'If that email is registered, a reset link has been sent.');
        }

        $authService = $this->authService;
        try {
            $authService->initiatePasswordReset($email, $user['id'], $this->request->getIp());
        } catch (\Exception $e) {
            $this->response->error($e->getMessage(), [], 500);
        }
        $this->response->success([], 'If that email is registered, a reset link has been sent.');
    }

    /*  
    * Verify reset token
     */
    public function verifyResetToken(){
        if (!$this->request->isPost()) {
            $this->response->error('Invalid request', [], 400);
        }

        $token = $this->request->post('token');
        if(!$token){
            $this->response->error('Token is required', [], 400);
        }

        try{
            $verifytoken = $this->authService->verifyResetToken($token);
        }catch(\Exception $e){
            $this->response->error($e->getMessage(), [], 400);
        }

        $this->response->success([], 'Token verified successfully');
    }

    /**
     * Resets User Password based on reset token
     * @return void
     */
    public function resetPassword(){
        if (!$this->request->isPost()) {
            $this->response->error('Invalid request', [], 400);
        }

        $token = $this->request->post('token');
        $password = $this->request->post('password');
        $confirmPassword = $this->request->post('confirm_password');

        if(!$token || !$password){
            $this->response->error('Token and Password are required', [], 400);
        }

        //validate password match
        if($password !== $confirmPassword){
            $this->response->error('Passwords do not match', [], 400);
        }

        try{
            $this->authService->resetPassword($token, $password);
        }catch(\Exception $e){
            $this->response->error($e->getMessage(), [], 400);
        }

        $this->response->success([], 'Password reset successfully');
    }
}
