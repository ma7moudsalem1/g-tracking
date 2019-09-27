<?php

namespace App\Http\Controllers\User\Profile;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller as BaseController;

class ProfileController extends BaseController 
{
    public function index(Request $request)
    {
        return $this->responseSuccess($request->auth, $message = 'Your profile data');
    }
}