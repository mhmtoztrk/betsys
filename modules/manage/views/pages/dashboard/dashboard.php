<?php

/** @var TYPE_NAME $user */
/** @var TYPE_NAME $view_type */

$admin_buttons = '';
$ab = new Admin_Buttons();

if($view_type == 'admin_panel'){
    $admin_buttons = $ab->output('user', $user);
}

$dashboard_items = [
    'bets' => [
        'icon' => FOOTBALL_ICON,
        'count' => 0,
        'title' => t('Bets'),
        'path' => manage_path('all-bets', 'user', $user['uid']),
    ],
    'balance' => [
        'icon' => MONEY_ICON,
        'count' => format_currency($user['balance']),
        'title' => t('Balance'),
        'path' => manage_path('credit-activities', 'user', $user['uid']),
    ],
    'total_stake' => [
        'icon' => MONEY2_ICON,
        'count' => format_currency(0),
        'title' => t('Total_Stake'),
        'path' => manage_path('credit-activities', 'user', $user['uid']),
    ],
    'total_winnings' => [
        'icon' => FINANCE_ICON,
        'count' => format_currency(0),
        'title' => t('Total_Winnings'),
        'path' => manage_path('credit-activities', 'user', $user['uid']),
    ],
];

$u_dashboard = require 'items.php';

$u_dashboard .= '<div class="p-3"><div class="row"><div class="col-sm-6">';

    $options = [
        'title' => t('Informations'),
        'items' => [
            'name' => [
                'label' => t('Name'),
                'value' => $user['full_name']
            ],
            'tel' => [
                'label' => t('Tel'),
                'value' => html_tel($user['tel'])
            ],
        ],
        'link' => [
            'path' => manage_path('information', 'user', $user['uid']),
            'title' => t('Edit'),
        ],
    ];

    $u_dashboard .= require 'blocks/summary.php';

$u_dashboard .= '</div>';

$u_dashboard .= '<div class="col-sm-6">';

    $options = [
        'title' => t('Account'),
        'items' => [
            'username' => [
                'label' => t('Username'),
                'value' => $user['username']
            ],
            'registration' => [
                'label' => t('Registration'),
                'value' => date('d/m/Y H:i', $user['created'])
            ],
        ],
        'link' => [
            'path' => manage_path('account', 'user', $user['uid']),
            'title' => 'Edit Username / Password',
        ],
    ];

    $u_dashboard .= require 'blocks/summary.php';

$u_dashboard .= '</div></div></div>';

return $admin_buttons.$u_dashboard;

?>