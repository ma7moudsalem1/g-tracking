<?php

namespace App\Http\Controllers\User\Auth;

use Validator;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Traits\JwtTrait;
use App\Http\Controllers\Controller as BaseController;

class AuthController extends BaseController 
{
    use JwtTrait;
    private $request;
    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function authenticate(User $user) {
        $this->validate($this->request, [
            'email'     => 'required|email',
            'password'  => 'required'
        ]);
        // Find the user by email
        $user = User::where('email', $this->request->input('email'))->first();
        if (!$user) {
            return $this->responseFail('Email does not exist.');
        }
        // Verify the password and generate the token
        if (Hash::check($this->request->input('password'), $user->password)) {
            return $this->responseSuccess([
                'token' => $this->jwt($user)
            ], 'user logged in.');
        }
        // Bad Request response
        return $this->responseFail('Email or password is wrong.');
    }
}