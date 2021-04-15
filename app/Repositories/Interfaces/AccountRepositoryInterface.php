<?php

namespace App\Repositories\Interfaces;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;

interface AccountRepositoryInterface
{
    public function all();
    public function register(Request $request);
}