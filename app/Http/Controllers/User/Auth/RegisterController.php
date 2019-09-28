<?php

namespace App\Http\Controllers\User\Auth;

use Validator;
use App\User;
use App\Models\UserLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller as BaseController;
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
            'phone'          => 'nullable|unique:users',
            'password'       => 'required|min:6|max:20',
            'company'        => 'nullable',
        ]);
        
        $inputs = $this->inputReady($inputs);

        $user = User::create($inputs);
        $newKey = $this->firebaseCreate('user_locations');
        $data = [
            'user_id'   => $user->id,
            'lng'       => '',
            'lat'       => ''
        ];
        $updates = [
            'user_locations/'.$newKey => $data
        ];

        $this->firebaseUpdate($updates);
        $data['dbkey'] = $newKey;
        UserLocation::create($data);
        return $this->responseSuccess([
            'token' => $this->jwt($user)
        ], 'user registered successfully.');
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
        $inputs['username'] = str_slug($inputs['name'], '_') . '_'. str_random(4);
        $inputs['password'] = $inputs['password'];
        return $inputs;
    }
}