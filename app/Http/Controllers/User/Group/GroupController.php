<?php

namespace App\Http\Controllers\User\Group;

use Validator;
use App\User;
use App\Models\Employee;
use App\Models\Group;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as BaseController;

class GroupController extends BaseController {

    public function index(Request $request)
    {
        $groups = $request->auth->isCompany ? $this->getCompanyGroups($request) : $request->auth->groups;
        return $this->responseSuccess($groups, 'group showen successfully.');
    }

    private function getCompanyGroups($request)
    {
        $auth      = $request->auth;
        $employees = Employee::where('company_id', $auth->id)->pluck('employee_id')->toArray();
        $groups    = Group::whereIn('user_id', $employees)->get();
        return $groups;
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'   => 'required|string|min:3|max:30',
            'limit'  => 'nullable|integer'
        ]);

        $auth      = $request->auth;
        $access    = $this->canAddMoreGroup($auth, $request);
        if(!$access['status']){
            return $this->responseErrors($access['message'], $request->all());
        }
        $exist = Group::where('name', $request->input('name'))->where('user_id', $auth->id)->count();
        if($exist){
            return $this->responseErrors(['name' => 'Already group exists'], $request->all());
        }
        $group = Group::create([
            'name'    => $request->input('name'),
            'limit'   => (int)$request->input('limit'),
            'user_id' => $auth->id
        ]);
        return $this->responseSuccess($group, 'group added successfully.');
    }

    private function canAddMoreGroup($auth, $request)
    {
        $groups = $auth->isCompany ? $this->getCompanyGroups($request) : $auth->groups;
        $limit  = $groups->sum('limit') + (int) $request->input('limit');
        if($auth->account_type == 'free' && count($groups) == 5){
            return ['status' => 0, 'message' => ['limit' => 'You have reached the limit creating group available for free account, upgrade to add more']];
        }

        if($auth->account_type == 'gold' && count($groups) == 30){
            return ['status' => 0, 'message' => ['limit' => 'You have reached the limit creating group available for gold account, upgrade to add more']];
        }

        if($auth->account_type == 'free' && $limit > 10){
            return ['status' => 0, 'message' => ['limit' => 'You have reached the limit available for free account, upgrade to add more']];
        }

        if($auth->account_type == 'gold' &&  $limit > 50){
            return ['status' => 0, 'message' => ['limit' => 'You have reached the limit available for gold account, upgrade to add more']];
        }

        return ['status' => 1, 'message' => 'can'];
    }

    public function show($id)
    {
        $group = Group::find($id);
        if(!$group){
            return $this->responseFail('group doesn\'t exist.', $request->all());
        }
        return $this->responseSuccess($group, 'group showen successfully.');
    }

    public function update($id, Request $request)
    {
        $this->validate($request, [
            'name'   => 'required|string|min:3|max:30',
        ]);

        $group = Group::whereId($id)->where('user_id', $request->auth->id)->first();
        if(!$group){
            return $this->responseFail('group doesn\'t exist.', $request->all());
        }
        $exist = Group::where('id', '!=', $group->id)->where('name', $request->input('name'))->where('user_id', $request->auth->id)->count();
        if($exist){
            return $this->responseErrors(['name' => 'Already group exists'], $request->all());
        }
        $group->update(['name' => $request->input('name')]);
        return $this->responseSuccess($group, 'group updated successfully.');
    }

    public function destroy($id, Request $request)
    {
        $group = Group::whereId($id)->where('user_id', $request->auth->id)->first();
        if(!$group){
            return $this->responseFail('group doesn\'t exist.', $request->all());
        }
        $group->delete();
        return $this->responseSuccess([], 'group deleted successfully.');
    }

}