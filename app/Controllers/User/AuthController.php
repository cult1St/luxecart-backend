<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Services\AuthService;
use Core\Database;
use Core\Request;
use Core\Response;
use Helpers\ClientLang;
use Helpers\ErrorResponse;
use Helpers\Validator;
use Throwable;

/** 
 * Authentication Controller
 * Handles user registration and login
 */

class AuthController extends BaseController
{
    private $validator;

    public function __construct(Database $db, Request $request, Response $response)
    {
        parent::__construct($db, $request, $response);
        $this->validator = new Validator();
    }
    /* =========================
     * User Registration
     * ========================= */
    public function register()
    {
        $this->requirePost();
        $data = $this->request->post();

        //validate input
        $this->validator->setValidations([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|minlen:6',
            'confirm_password' => 'required|string|equalsfield:password',
        ]);
        if (!$this->validator->runValidations($data)) {
            return $this->response->error(ClientLang::REQUIRED_FIELDS, Response::UnprocessedEntity, $this->validator->getValidationErrors());
        }

        try {
            $registeredUser = $this->authService->registerUser($data);
            return $this->response->success($registeredUser, ClientLang::REGISTER_SUCCESS_VERIFY);
        } catch (Throwable $e) {
            $this->log("Registration error: " . $e->getMessage(), 'error'); 
            return $this->response->error(ErrorResponse::formatResponse($e), $e->getCode() ?? Response::BadRequest);
        }
    }

    /* =========================
     * Email Verification
     * ========================= */
    public function verifyEmail()
    {
        $this->requirePost();
        $data = $this->request->post();
        //validate Request
        $this->validator->setValidations([
            'verification_code' => 'required|string',
        ]);
        if (!$this->validator->runValidations($data)) {
            return $this->response->error(ClientLang::REQUIRED_FIELDS, Response::UnprocessedEntity, $this->validator->getValidationErrors());
        }

        try {
            $verifiedUser = $this->authService->verifyEmail($data['verification_code']);
            return $this->response->success($verifiedUser, ClientLang::VERIFY_SUCCESS);
        } catch (Throwable $e) {
            $this->log("Email verification error: " . $e->getMessage(), 'error');
            return $this->response->error(ErrorResponse::formatResponse($e), $e->getCode() ?? Response::BadRequest);
        }
    }

    /* =========================
     * User Login
     * ========================= */
    public function login(){
        $this->requirePost();

        $data = $this->request->post();

        //validate input
        $this->validator->setValidations([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        if (!$this->validator->runValidations($data)) {
            return $this->response->error(ClientLang::REQUIRED_FIELDS, Response::UnprocessedEntity, $this->validator->getValidationErrors());
        }

        //process login
        try{
            
        }catch (Throwable $e) {
            $this->log("Login error: " . $e->getMessage(), 'error');
            return $this->response->error(ErrorResponse::formatResponse($e), $e->getCode() ?? Response::BadRequest);
        }
    }
}
