<?php

namespace App\Http\Controllers\User\Location;

use Validator;
use App\User;
use App\Models\Group;
use App\Models\Employee;
use App\Models\UserLocation;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as BaseController;

class LocationController extends BaseController {

    public function update(Request $request)
    {
        $user = User::find($request->auth->id);
        $location = $user->location;

        $data = [
            'user_id' => $request->auth->id,
            'lng'     => $request->lng,
            'lat'     => $request->lat
        ];

        $updates = [
            'user_locations/'.$location->dbkey => $data,
        ];

        $this->firebaseUpdate($updates);
        
        $location->update($data);
        return $this->responseSuccess([], 'location updated successfully.');
    }

    public function getGroupLocation($id, Request $request)
    {
        $group = Group::find($id);
        if(!$group){
            return $this->responseFail('group doesn\'t exist.', $request->all());
        }
        $users = $group->userGroup()->pluck('user_id');

        $locations = UserLocation::whereIn('user_id', $users)->with('user')->get();
        return $this->responseSuccess(['locations' => $locations], 'locations showen successfully.');
    }
}