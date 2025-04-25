<?php

namespace Response;

class Manage
{

    public $type;
    public $view_type = 'admin_panel';

    public function user_panel_redirect($uid){
        go(PANEL_PATH.'/user/'.$uid.'/dashboard');
    }

    public function panel_user_uid_page($uid, $page_key){
        $u = new \User();
        $user = $u->load($uid);
        if($user){
            return $this->user_manage_page($user, $page_key);
        }
        return PAGE_NOT_FOUND;
    }

    public function front_redirect(){
        go('/manage/matches');
    }

    public function front_page($page_key){
        
        global $user;

        if ($user['uid']){

            $this->view_type = 'front';

            if($user['role'] == 'standard'){

                return $this->user_manage_page($user, $page_key);

            }
            return PAGE_NOT_FOUND;

        }else{

            go('/');

        }

    }


    //MANAGE OUTPUTS

    public function user_manage_page($user, $page_key){
        $page_key = str_replace('-','_',$page_key);

        set_page_title($user['full_name']);

        set_content('page', [
            'type' => 'user',
            'active_page' => $page_key,
            'view_type' => $this->view_type,
            'user' => $user,
        ]);
        
    }

}