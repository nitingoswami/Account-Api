<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use App\Repositories\Interfaces\AccountRepositoryInterface;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private $accountRepository;

    public function __construct(AccountRepositoryInterface $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    public function account()
    {
        $data = $this->accountRepository->all();
        dd($data);
        return view('blog')->withBlogs($blogs);
    }

    public function register(Request $request)
    {
       return 'new';
    }
}
