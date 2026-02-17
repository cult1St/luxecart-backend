<?php 
namespace App\Models;

class User extends BaseModel
{
    protected string $table = "users";

    protected array $fillable = [
        "name",
        "email",
        "phone",
        "address",
        "city_id",
        "state_id",
        "is_verified",
        "password",
    ];

    /**
     * create a new user
     */
    public function createUser(array $data): ?object
    {
       $data["password"] = password_hash($data["password"], PASSWORD_BCRYPT);
       $create = $this->create($data);
       if($create){
          return $this->find($create);
       }
       return null;
        
    }

    public function findByEmail(string $email): ?object
    {
        return $this->findBy('email', $email);
    }

}