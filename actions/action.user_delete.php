<?php

namespace Action;

class user_delete extends action_delete
{

    public function __construct($pars){

        parent::__construct([
            'class' => '\User',
            'id_field' => 'uid',
            'texts' => [
                'confirmation' => [
                    'bulk' => [
                        'title' => t('Delete_Users'),
                        'desc' => 'Deleting these users will remove all related data (bets, credit records, etc.). It\'s strongly recommended to block the users instead to retain historical data.',
                        'apply_button' => t('Delete'),
                    ],
                    'single' => [
                        'title' => t('Are_you_sure_to_delete_user'),
                        'desc' => 'Deleting this user will remove all related data (bets, credit records, etc.). It\'s strongly recommended to block the user instead to retain historical data.',
                        'apply_button' => t('Delete'),
                    ],
                ],
                'deleted' => [
                    'bulk' => t('Users_deleted'),
                    'single' => t('User_deleted'),
                ],
            ],
            'pars' => $pars,
            'redirect' => '/'.PANEL_PATH.'/users',
        ]);
    }

}