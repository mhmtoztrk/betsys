<?php

namespace Action;

class user_unblock extends \User
{

    public $user = NULL;
    public $multi_type = 'single';
    public $uids = [];

    public function __construct($pars){
        if (isset($pars['ids'])){

            $this->multi_type = 'bulk';
            $this->uids = $pars['ids'];

        }elseif (isset($pars['uid'])){

            $user = \User::load($pars['uid']);
            if ($user) $this->user = $user;

            $this->multi_type = 'single';
            $this->uids = [$pars['uid']];

        }
    }

    public function confirmation(){
        if ($this->multi_type == 'bulk'){
            return [
                'title' => t('Unblock_Selected_Users'),
                'desc' => 'If you unblock the users, system will be allowed for the users',
                'apply_button' => [t('Unblock'),'success'],
            ];
        }else{
            return [
                'title' => $this->user['full_name'].' - '.t('Unblock'),
                'desc' => 'If you unblock the user, system will be allowed for the user',
                'apply_button' => [t('Unblock'),'success'],
            ];
        }
    }

    public function access(){
        $access = TRUE;
        if (role_access('admin')){
            if ($this->multi_type == 'bulk'){
                $uids = $this->uids;

                foreach($uids as $uid){
                    $user = \User::load($uid);
                    if (!$user) $access = FALSE;
                }
            }else{
                if (!$this->user) $access = FALSE;
            }

        }else{
            $access = FALSE;
        }

        return $access;
    }

    public function apply(){

        $uids =$this->uids;

        foreach ($uids as $uid){
            $this->unblock($uid);

            \JS::live('add_class', [
                'element' => '#p-'.$uid,
                'class' => 'active-1',
            ]);

            \JS::live('remove_class', [
                'element' => '#p-'.$uid,
                'class' => 'active-0',
            ]);

        }

        if ($this->multi_type == 'bulk'){

            \JS::live('message', [
                'type' => 'info',
                'message' => t('Users_unblocked'),
            ]);

        }else{

            \JS::live('reload');

            \JS::session('message', [
                'type' => 'info',
                'message' => t('User_unblocked'),
            ]);

        }

    }

}