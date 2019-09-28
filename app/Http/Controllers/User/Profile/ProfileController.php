<?php

namespace App\Http\Controllers\User\Profile;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller as BaseController;
use App\Traits\UploadTrait;

class ProfileController extends BaseController 
{
    use UploadTrait;
    public function index(Request $request)
    {
        return $this->responseSuccess($request->auth, 'Your profile data');
    }

    public function update(Request $request)
    {
        $id = $request->auth->id;

        $inputs = $this->validate($request, [
            'first_name'     => 'required|string|min:3|max:21',
            'last_name'      => 'required|string|min:3|max:21',
            'email'          => 'required|email|unique:users,email,'. $id,
            'phone'          => 'nullable|numaric|unique:users,phone,'. $id,
            'avatar'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);
        if($request->has('avatar')){
            $image  = $request->file('avatar');
            $upload = $this->uploadOne($image);
            $inputs['avatar'] = $upload['fullName'];
        }else{
            $inputs['avatar'] = $request->auth->avatar;
        }

        $user = User::find($id);
        $user->update($inputs);
        $request->auth = $user;
        return $this->responseSuccess($user, 'Your profile data updated successfully');
    }

    public function updateAvatar(Request $request)
    {
        $id = $request->auth->id;

        $inputs = $this->validate($request, [
            'avatar'         => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if($request->has('avatar')){
            $image  = $request->file('avatar');
            $upload = $this->uploadOne($image);
            $inputs['avatar'] = $upload['fullName'];
        }
        $user = User::find($id);
        $user->update($inputs);
        $request->auth = $user;
        return $this->responseSuccess($user, 'Your profile photo updated successfully');
    }

    public function password(Request $request)
    {
        $this->validate($request, [
            'old_password'  => 'required|string',
            'password'      => 'required|string|min:6|max:21',
        ]);
        $id = $request->auth->id;
        $user = User::find($id);
        if (!Hash::check($request->old_password, $user->password)) {
            return $this->responseErrors(['old_passowrd' => 'this password is not match with current database']);
        }

        $user->update(['password' => $request->password]);
        return $this->responseSuccess($user, 'Your password updated successfully.');
    }
}