<?php 
namespace App\Models;

class Admin extends BaseModel
{
    protected string $table = "admins";

    protected array $fillable = [
        "name",
        "email",
        "role",
        "password",
    ];

}