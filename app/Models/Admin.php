<?php 
namespace App\Models;

class Admin extends BaseModel
{
    protected string $table = "users";

    protected array $fillable = [
        "name",
        "email",
        "role",
        "password",
    ];

}