<?php

namespace App\Http\Controllers\User\Invite;

use Validator;
use App\User;
use App\Models\Group;
use App\Models\Employee;
use App\Models\UserInvitation;
use App\Models\GroupInvitation;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as BaseController;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use Kreait\Firebase;

class InvitationController extends BaseController {

    private $request;
    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function sendEmployeeInvitation()
    {
        $request = $this->request;
        
        if(!$request->auth->isCompany){
            return response()->json([
                'error' => 'Your account does not have right role.'
            ], 400);
        }

        if(!$request->auth->company_status){
            return response()->json([
                'error' => 'you still can not send invite to the employees.'
            ], 400);
        }

        $input = $request->input('employee');
        $employee = User::where('username', $input)->orWhere('email', $input)->orWhere('phone', $input)->first();
        if(!$employee){
            return response()->json([
                'error' => 'Employee does not exist.'
            ], 400);
        }
        if($employee->isCompany){
            return response()->json([
                'error' => 'Not valid employee.'
            ], 400);
        }

        $inviteExist = Employee::where('company_id', $request->auth->id)->where('employee_id', $employee->id)->count();
        if($inviteExist){
            return response()->json([
                'error' => 'You already invated this employee before.'
            ], 400);
        }

        Employee::create(['company_id' => $request->auth->id, 'employee_id' => $employee->id]);
        return response()->json([
            'success' => 'invitation has been sent.'
        ], 200);
    }

    public function sendInvitationGroup(Request $request)
    {
        $group = Group::where('id', $request->input('group'))->where('user_id', $request->auth->id)->first();
        if(!$group){
            return $this->responseFail('The group id not valid.', $request->all());
        }
        
        if(UserInvitation::where('user_id', $request->input('user'))->where('group_id', $request->input('group'))->count()){
            return $this->responseErrors(['user' => 'You already invated this person before.'], $request->all());
        }

        $firebase = (new Factory)
        ->withServiceAccount(__DIR__ . '/gtracking-be02c-firebase-adminsdk-1f6i9-6aa811c257.json')
        ->create();
        $db = $firebase->getDatabase();

        $data = [
            'sender_id' => $request->auth->id,
            'user_id'   => $request->input('user'),
            'group_id'  => $request->input('group'),
            'status'    => 0
        ];

        $newKey = $db->getReference('group_invitations')->push()->getKey();
        $updates = [
            'group_invitations/'.$newKey => $data,
        ];
        $db->getReference()->update($updates);
        $data['dbkey'] = $newKey;
        UserInvitation::create($data);
        return $this->responseSuccess([], 'the invitation has been sent.');
    }

    public function acceptGroupInvitation(Request $request)
    {
        $Invitation = UserInvitation::where('user_id', $request->auth->id)->where('group_id', $request->input('group'))->first();
        if(!$Invitation){
            return $this->responseFail('The invitation id is not valid.', $request->all());
        }

        $firebase = (new Factory)
        ->withServiceAccount(__DIR__ . '/gtracking-be02c-firebase-adminsdk-1f6i9-6aa811c257.json')
        ->create();
        $db = $firebase->getDatabase();
        $data = [
            'sender_id' => $Invitation->sender_id,
            'user_id'   => $request->auth->id,
            'group_id'  => $request->input('group'),
            'status'    => 1
        ];

        $updates = [
            'group_invitations/'.$Invitation->dbkey => $data,
        ];
        $db->getReference()->update($updates);
        $Invitation->update(['status' => 1]);
        return $this->responseSuccess([], 'the invitation has been accepted.');
    }

    public function createGroupCodeForInvite(Request $request)
    {
        $group = Group::where('id', $request->input('group'))->where('user_id', $request->auth->id)->first();
        if(!$group){
            return $this->responseFail('The group id not valid.', $request->all());
        }

        $exist = GroupInvitation::where('group_id', $request->input('group'))->first();

        if($exist){
            $invitation = $exist;
        }else{
            $invitation = GroupInvitation::create([
                'group_id' =>  $request->input('group'),
                'user_id'  =>  $request->auth->id,
                'slug'     =>  str_random(5). rand(0, 999)
            ]);
        }
        
        return $this->responseSuccess($invitation, 'invitation created successfully.');
    }

    public function AcceptGroupCodeForInvite(Request $request)
    {
        $invitation = GroupInvitation::where('slug', $request->input('code'))->first();
        if(!$invitation){
            return $this->responseFail('The invitation code is not valid.', $request->all());
        }

        $isExist = UserGroup::where('user_id', $request->auth->id)->where('group_id', $invitation->group_id)->count();
        if($isExist){
            return $this->responseFail('You are already in this group.', $request->all());
        }
        UserGroup::create([
            'user_id'  => $request->auth->id,
            'group_id' => $invitation->group_id
        ]);
        return $this->responseSuccess([], 'you are in this group now.');
    }

}