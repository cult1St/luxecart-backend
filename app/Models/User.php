<?php 
namespace App\Models;

class User extends BaseModel
{
    protected $table = "users";

    protected $fillable = [
        "name",
        "email",
        "phone",
        "address",
        "city_id",
        "state_id",
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

}