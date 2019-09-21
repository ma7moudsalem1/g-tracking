<?php

namespace App\Http\Controllers\User\Group;

use Validator;
use App\User;
use App\Models\Employee;
use App\Models\Group;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class GroupController extends BaseController {

    public function index(Request $request)
    {
        $groups = $request->auth->isCompany ? $this->getCompanyGroups($request) : $request->auth->groups;
        return response()->json(compact('groups'), 200);
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
        $auth      = $request->auth;
        $access    = $this->canAddMoreGroup($auth, $request);
        if(!$access['status']){
            return response()->json(['error' => $access['message']], 400);
        }
        $exist = Group::where('name', $request->input('name'))->where('user_id', $auth->id)->count();
        if($exist){
            return response()->json(['error' => 'Already group exists'], 400);
        }
        $group = Group::create([
            'name'    => $request->input('name'),
            'limit'   => (int)$request->input('limit'),
            'user_id' => $auth->id
        ]);
        return response()->json(compact('group'), 200);
    }

    private function canAddMoreGroup($auth, $request)
    {
        $groups = $auth->isCompany ? $this->getCompanyGroups($request) : $auth->groups;
        $limit  = $groups->sum('limit') + (int) $request->input('limit');
        if($auth->account_type == 'free' && count($groups) == 5){
            return ['status' => 0, 'message' => 'You have reached the limit creating group available for free account, upgrade to add more'];
        }

        if($auth->account_type == 'gold' && count($groups) == 30){
            return ['status' => 0, 'message' => 'You have reached the limit creating group available for gold account, upgrade to add more'];
        }

        if($auth->account_type == 'free' && $limit > 10){
            return ['status' => 0, 'message' => 'You have reached the limit available for free account, upgrade to add more'];
        }

        if($auth->account_type == 'gold' &&  $limit > 50){
            return ['status' => 0, 'message' => 'You have reached the limit available for gold account, upgrade to add more'];
        }

        return ['status' => 1, 'message' => 'can'];
    }

    public function show($id)
    {
        $group = Group::find($id);
        if(!$group){
            return response()->json(['error' => 'group doesn\'t exist.'], 400);
        }
        return response()->json(compact('group'), 200);
    }

    public function update($id, Request $request)
    {
        $group = Group::whereId($id)->where('user_id', $request->auth->id)->first();
        if(!$group){
            return response()->json(['error' => 'group doesn\'t exist.'], 400);
        }
        $exist = Group::where('id', '!=', $group->id)->where('name', $request->input('name'))->where('user_id', $request->auth->id)->count();
        if($exist){
            return response()->json(['error' => 'Already group exists'], 400);
        }
        $group->update(['name' => $request->input('name')]);
        return response()->json(compact('group'), 200);
    }

    public function destroy($id, Request $request)
    {
        $group = Group::whereId($id)->where('user_id', $request->auth->id)->first();
        if(!$group){
            return response()->json(['error' => 'group doesn\'t exist.'], 400);
        }
        $group->delete();
        return response()->json(['success' => 'group deleted successfully.']);
    }

}