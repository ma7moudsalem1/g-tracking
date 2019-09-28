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

class InvitationController extends BaseController {

    private $request;
    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function sendEmployeeInvitation()
    {
        $request = $this->request;
        
        if(!$request->auth->isCompany){
            return $this->responseFail('Your account does not have right role.', $request->all());
        }

        if(!$request->auth->company_status){
            return $this->responseFail('you still can not send invite to the employees.', $request->all());
        }

        $input = $request->input('employee');
        $employee = User::where('username', $input)->orWhere('email', $input)->orWhere('phone', $input)->first();
        if(!$employee){
            return $this->responseFail('Employee does not exist.', $request->all());
        }
        if($employee->isCompany){
            return $this->responseFail('Not valid employee.', $request->all());
        }

        $inviteExist = Employee::where('company_id', $request->auth->id)->where('employee_id', $employee->id)->count();
        if($inviteExist){
            return $this->responseFail('You already invated this employee before.', $request->all());
        }

        Employee::create(['company_id' => $request->auth->id, 'employee_id' => $employee->id]);
        return $this->responseSuccess([], 'the invitation has been sent.');
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


        $data = [
            'sender_id' => $request->auth->id,
            'user_id'   => $request->input('user'),
            'group_id'  => $request->input('group'),
            'status'    => 0
        ];

        $newKey = $this->firebaseCreate('group_invitations');
        $updates = [
            'group_invitations/'.$newKey => $data,
        ];
        $this->firebaseUpdate($updates);
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

        $data = [
            'sender_id' => $Invitation->sender_id,
            'user_id'   => $request->auth->id,
            'group_id'  => $request->input('group'),
            'status'    => 1
        ];

        $updates = [
            'group_invitations/'.$Invitation->dbkey => $data,
        ];
        $this->firebaseUpdate($updates);
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