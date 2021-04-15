<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use App\Models\Client;
use App\Models\User;
use App\Repositories\AccountRepository;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private $accountRepository;

    public function __construct(AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    public function account()
    {
        $data = $this->accountRepository->all();
        return $data;
    }

    public function register(Request $request)
    {
        $data = $this->accountRepository->register($request);
        return $data;
    }
}
