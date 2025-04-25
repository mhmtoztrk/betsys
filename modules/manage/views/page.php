<?php

$pars = get_content_pars();

$lang = $pars['lang'] ?? current_lang();
$type = $pars['type'];
$active_page = $pars['active_page'];
$view_type = $pars['view_type'];

$u = new User();

$sidebar_footer = '';

if($type == 'user'){

    $user = $pars['user'];
    $uid = $user['uid'];
    $id = $user['uid'];
    $logo = $user['image'];
    $title = $user['full_name'];

    $manage_items = menu_views($view_type);

    \Breadcrumb::set(PANEL_PATH.'/users', 'Users');
    if($active_page != 'dashboard') \Breadcrumb::set(PANEL_PATH.'/user/'.$user['uid'].'/dashboard', $user['full_name']);

}

$m_path = m_path($view_type);
$manage_menus =  manage_menus([
    'manage_menus' => $manage_items,
    'type' => $type,
    'view_type' => $view_type,
    'id' => $id,
]);

if($view_type == 'front' && $type = 'user'){

    $manage_path = $lang == DEF_LANG ? '' : $lang.'/';
    $manage_path .= 'manage/'.$active_page;
    $manage_menus['other']['items'] = [
        'logout' => [
            'menu_label' => '<i class="fas fa-sign-out-alt"></i> '.t('Logout'),
            'path' => '/logout',
            'attrs' => [
                'class' => ['logout-button']
            ],
        ],
    ];

}elseif($view_type == 'admin_panel'){

    $manage_path = PANEL_PATH.'/'.$type.'/'.$id.'/'.$active_page;

}

$items = menu_items();

$manage_toggle = '';
if ($view_type == 'front'){
    $manage_toggle .= '<div id="manage-title"><div class="container">';

    $manage_toggle .= '<div class="toggle-menu">
                        <ul class="topbar-nav">
                            <li class="topbar-nav-item relative">
                                <a class="toggle-nav" href="#">
                                    <div class="toggle-icon">
                                        <span class="toggle-line"></span>
                                        <span class="toggle-line"></span>
                                        <span class="toggle-line"></span>
                                        <span class="toggle-line"></span>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>';

    $manage_toggle .='<div class="view-title">'.$title.' - '.t('My_Account').'</div>';

    $manage_toggle .='</div></div>';
}

$show = $items[$active_page]['show'][$view_type] ?? TRUE;
if(!$show) return '';

set_page_title($items[$active_page]['title']);

if (isset($m_path[0]) && file_exists(module_dir('manage').'/views/pages/'.$active_page.'/'.$m_path[0].'.php')){
    $content = require 'pages/'.$active_page.'/'.$m_path[0].'.php';
}else{
    $content = require 'pages/'.$active_page.'/'.$active_page.'.php';
}

$menu = require_once 'menu/menu.php';

$show_title_on_page = $items[$active_page]['show_title_on_page'] ?? TRUE;

$content_title = $show_title_on_page ? '<h1>'.$items[$active_page]['title'].'</h1>' : '';

return '
<div class="manage-container manage-'.$view_type.'">
    '.content_message_block('manage-top').'

    <div id="manage-output">

        <div id="manage-sidebar" class="manage-main-block">
            <div class="user-profile-block">
                <div class="user-title">
                    <div class="user-panel-title">'.$title.'</div>
                    <a class="toggle-nav" href="#">
                        <div class="toggle-icon">'.MENU_TOGGLE_ICON.'</div>
                    </a>
                </div>
            </div>
            '.$menu.$sidebar_footer.'
        </div>

        <div id="manage-content" class="manage-main-block">
            '.content_message_block('manage-content-top').'
                '.$content_title.'
            <div class="manage-content-container">
                '.$content.'
            </div>
        </div>

    </div>
</div>';


?>