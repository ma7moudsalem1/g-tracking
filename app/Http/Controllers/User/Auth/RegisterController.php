<?php

namespace App\Http\Controllers\User\Auth;

use Validator;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\JwtTrait;

class RegisterController extends BaseController 
{
    use JwtTrait;
    private $request;
    public function __construct(Request $request) {
        $this->request = $request;
    }
    
    public function register(User $user) {

        $request = $this->request;

        $inputs = $this->validate($request, [
            'first_name'     => 'required|string|min:3|max:21',
            'last_name'      => 'required|string|min:3|max:21',
            'email'          => 'required|email|unique:users',
            'phone'          => 'nullable|numaric|unique:users',
            'password'       => 'required|min:6|max:20',
            'company'        => 'nullable|boolean',
        ]);
        
        $inputs = $this->inputReady($inputs);

        $user = User::create($inputs);
        
        return response()->json([
            'token' => $this->jwt($user)
        ], 200);
    }

    private function inputReady(array $inputs)
    {
        $request = $this->request;
        if($request->input('company') != null){
            $inputs['isCompany'] = (boolean) $request->input('company');
        }
        if($request->input('phone') == null){
            unset($inputs['phone']);
        }
        $inputs['name']     = $request->input('first_name') . ' ' . $request->input('last_name');
        $inputs['username'] = str_slug($inputs['name'], '_') . str_random(4);
        $inputs['password'] = Hash::make($inputs['password']);
        return $inputs;
    }
}