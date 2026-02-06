<?php 
namespace App\Models;

class Admin extends BaseModel
{
    protected $table = "users";

    protected $fillable = [
        "name",
        "email",
        "role",
        "password",
    ];

}