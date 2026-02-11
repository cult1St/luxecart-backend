<?php 
namespace App\Services;

use App\Models\Admin;
use App\Models\EmailVerification;
use App\Models\User;
use Core\Database;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Helpers\ClientLang;
use Helpers\Mailer;
use Helpers\Utility;
use Throwable;

class AuthService
{
    private Utility $utility;
    private User $userModel;
    private Admin $adminModel;
    public function __construct(
        private Database $db
    )
    {
        $this->utility = new Utility($this->db);
        $this->userModel = new User($this->db);
        $this->adminModel = new Admin($this->db);
    }

    public function generateToken(object $user, string $type = 'user'): string{
        //implementing using firebase jwt
        $payload = [
            "sub" => $user->id,
            "type" => $type,
            "token" => $this->utility->randID('alphanumeric', 32),
            "iat" => time(),
            "exp" => time() + (60 * 60) // 1 hour,
        ];

        //generate token using firebase jwt
        $jwt = JWT::encode($payload, env('JWT_SECRET_KEY'), 'HS256');

        return $jwt;
    }

    public function validateToken(string $token, string $type = 'user'): object
    {
        try{
            $jwt = JWT::decode($token, new Key(env('JWT_SECRET_KEY'), 'HS256'));
            if($jwt->type !== $type){
                throw new Exception('Invalid token');
            }
            //check if token has expired
            if($jwt->exp < time()){
                throw new Exception('Token has expired');
            }
            //check if user has been logged out
            if($this->utility->isTokenBlacklisted($jwt->token)){
                throw new Exception('Token has been invalidated');
            }

            //get the user/admin
            if($type === 'user'){
                $user = $this->userModel->find($jwt->sub);
            }else{
                $user = $this->adminModel->find($jwt->sub);
            }
            return $user;
        } catch (Throwable $e) {
            throw new Exception("Invalid token");
        }
    }

    /**
     * Register a new user
     */
    public function registerUser(array $data): array{
        //check if emails already exists
        if($this->userModel->findBy("email", $data["email"])){
            throw new Exception(ClientLang::EMAIL_EXIST, 412);
        }
        //create user
        $user = $this->userModel->createUser($data);
        if($user){
            $this->createEmailVerification($user->id, $user->email);
            return [
                "id" => $user->id,
                "name" => $user->name,
                "email" => $user->email,
            ];
        }
        throw new Exception(ClientLang::REGISTER_FAILED, 400);
    }

    public function createEmailVerification(int $userId, string $email): object
    {
        $emailVerificationModel = new EmailVerification($this->db);
        $mailer = new Mailer();

        $verification = $emailVerificationModel->createVerification($userId, $email);

        //send via email
        $mailer->sendVerificationCode($email, $verification->name, $verification->code);
        return $verification;
    }

    public function verifyEmail(string $code): array
    {
        $emailVerificationModel = new EmailVerification($this->db);
        $mailer = new Mailer();

        //verify code
        $verification = $emailVerificationModel->verifyCode($code);
        if(!$verification){
            throw new Exception("Invalid Verification Code", 400);
        }
        //update user as verified
        $this->userModel->update($verification->user_id, ["is_verified" => 1]);
        //get user details
        $user = $this->userModel->find($verification->user_id);
        //send welcome email
        $mailer->sendWelcomeEmail($verification->email, $user->name);
        return [
            "user_id" => $verification->user_id,
            "email" => $verification->email,
        ];
    }
}