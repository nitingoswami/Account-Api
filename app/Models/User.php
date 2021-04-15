<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;
    protected $table = "users";

    protected $fillable = [
        'client_id', 'first_name', 'last_name',
        'email', 'password', 'phone', 'profile_uri',
        'last_password_reset', 'status'
    ];
}
