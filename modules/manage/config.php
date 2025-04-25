<?php

//Front - Manage

add_routing([
    'path' => [
        0 => 'manage',
        1 => NULL,
    ],
    'run' => 'manage/front_redirect',
]);

add_routing([
    'path' => [
        0 => 'manage',
        1 => STR,
    ],
    'run' => 'manage/front_page',
    'pars' => [1],
]);

function m_path($type = 'admin_panel'){
    $path = path();
    if($type == 'admin_panel'){
        $c = 4;
    }elseif ($type == 'front'){
        $c = current_lang() == DEF_LANG ? 2 : 3;
    }
    for($i = 0; $i < $c; $i++){
        if(isset($path[$i])) unset($path[$i]);
    }
    return array_values($path);
}

function manage_path($page, $type = NULL, $id = NULL){
    global $user;
    $path = '/';
    if($user['role'] == 'admin'){
        $path .= PANEL_PATH.'/'.$type.'/'.$id;
    }else{
        $path .= current_lang() == DEF_LANG ? '' : current_lang().'/';
        $path .= 'manage';
    }
    return BASE_PATH.$path.'/'.$page;
}

function user_manage_link($user){
    $link = BASE_URL.'/';
    if ($user['lang'] != DEF_LANG) $link .= $user['lang'].'/';
    return $link.'manage';
}

function manage_menus($vars){

    $manage_menus = $vars['manage_menus'];
    $type = $vars['type'] ?? 'user';
    $view_type = $vars['view_type'] ?? 'admin_panel';

    $lang = current_lang();

    $items = menu_items();

    $groups = menu_groups();

    $list = [];

    foreach ($manage_menus as $group_key => $group_items){

        $list[$group_key] = $groups[$group_key];

        foreach ($group_items as $page){
            if($view_type == 'admin_panel'){
                $path = '/panel/'.$type.'/'.$vars['id'].'/'.$page;
            }elseif($view_type == 'front'){
                $path = $lang == DEF_LANG ? '' : '/'.$lang;
                $path .= '/manage/'.$page;
            }

            $list[$group_key]['items'][$page] = [
                'menu_label' => $items[$page]['icon'].' '.$items[$page]['menu_label'],
                'path' => $path,
                'attrs' => $items[$page]['attrs'] ?? [],
            ];
        }
    }

    return $list;
}

function menu_groups(){

    return [
        'base' => [
            // 'title' => t('Manage'),
            'items' => [],
        ],
        'create_bet' => [
            'title' => [
                'admin_panel' => t('Create_Bet'),
                'front' => t('Create_Bet'),
            ],
            'items' => [],
        ],
        'my_bets' => [
            'title' => [
                'admin_panel' => t('User_Bets'),
                'front' => t('My_Bets'),
            ],
            'items' => [],
        ],
        'credit' => [
            'title' => [
                'admin_panel' => t('Credit'),
                'front' => t('Credit'),
            ],
            'items' => [],
        ],
        'settings' => [
            'title' => [
                'admin_panel' => t('Account'),
                'front' => t('My_Account'),
            ],
            'items' => [],
        ],
    ];

}

function menu_items(){
    
    $items = [
        'dashboard' => [
            'menu_label' => t('Dashboard'),
            'title' => t('Dashboard'),
            'show_title_on_page' => FALSE,
            'show' => [
                'front' => FALSE,
            ],
            'icon' => DASHBOARD_ICON,
        ],
        'matches' => [
            'menu_label' => t('Matches'),
            'title' => t('Matches'),
            'show_title_on_page' => FALSE,
            'show' => [
                'admin_panel' => FALSE,
            ],
            'icon' => GAMES_ICON,
            'attrs' => [
                'class' => ['matches-menu-item']
            ],
        ],
        'active_bets' => [
            'menu_label' => t('Active_Bets'),
            'title' => t('Active_Bets'),
            'icon' => ACTIVE_BET_ICON,
        ],
        'all_bets' => [
            'menu_label' => t('All_Bets'),
            'title' => t('All_Bets'),
            'icon' => PASSIVE_BET_ICON,
        ],
        'credit_action' => [
            'menu_label' => t('Add_Credit_Action'),
            'title' => t('Add_Credit_Action'),
            'icon' => CREDIT_ACTION_ICON,
        ],
        'withdraw' => [
            'menu_label' => t('Withdraw'),
            'title' => t('Withdraw'),
            'icon' => CREDIT_ACTION_ICON,
        ],
        'credit_activities' => [
            'menu_label' => t('Credit_Activities'),
            'title' => t('Credit_Activities'),
            'icon' => FINANCE_ICON,
        ],
        'information' => [
            'menu_label' => t('Information'),
            'title' => t('Information'),
            'icon' => INFO_ICON,
        ],
        'account' => [
            'menu_label' => t('Account_Settings'),
            'title' => t('Account_Settings'),
            'icon' => ACCOUNT_ICON,
        ],
    ];

    return $items;

}

function menu_views($view_type){

    if($view_type == 'front'){

        return [
            'base' => ['matches'],
            'my_bets' => ['active_bets','all_bets'],
            'settings' => ['credit_activities','information','account'],
        ];
    
    }elseif($view_type == 'admin_panel'){

        return [
            'base' => ['dashboard'],
            'my_bets' => ['active_bets','all_bets'],
            'credit' => ['credit_action','withdraw','credit_activities'],
            'settings' => ['information','account'],
        ];
    
    }
}