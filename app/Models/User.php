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

}