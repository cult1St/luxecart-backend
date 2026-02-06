<?php 
namespace App\Services;

use App\Models\Admin;
use App\Models\User;
use Core\Database;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
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
}