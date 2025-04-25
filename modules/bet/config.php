<?php

define('MAX_WINNINGS', 10000);
define('SLIP_DELAY', 10);

require_once_all(CUSTOM_MODULES.'/bet/classes');
require_once_all(CUSTOM_MODULES.'/bet/view_helpers');

//All leagues
add_routing([
    'path' => [
        0 => PANEL_PATH,
        1 => 'leagues',
        2 => NULL,
    ],
    'run' => 'bet/leagues',
]);

//Active leagues
add_routing([
    'path' => [
        0 => PANEL_PATH,
        1 => 'leagues',
        2 => 'active',
    ],
    'run' => 'bet/active_leagues',
]);

//All bet types
add_routing([
    'path' => [
        0 => PANEL_PATH,
        1 => 'bet-types',
        2 => NULL,
    ],
    'run' => 'bet/bet_types',
]);

//Edit bet type
add_routing([
    'path' => [
        0 => PANEL_PATH,
        1 => 'bet-type',
        2 => INT,
        3 => 'edit',
    ],
    'run' => 'bet/edit',
    'pars' => [2],
]);

//Bet ajax actions
add_routing([
    'path' => [
        0 => 'bet-action',
        1 => STR,
        2 => NULL,
    ],
    'run' => 'bet/bet_action',
    'pars' => [1],
]);


add_routing([
    'path' => [
        0 => 'api-test',
        1 => NULL,
    ],
    'run' => 'bet/bet_test',
]);

function col_amount($datas){
    $amount = $datas['entity'][$datas['amount_field']];
    return '<div class="credit-amount">'.format_currency($amount).'</div>';
}

function col_credit_type($datas){
    $view_type = $datas['view_type'];
    $uid = $datas['uid'];

    $type = $datas['entity'][$datas['type_field']];
    $way = $datas['entity'][$datas['way_field']];
    $slip_id = $datas['entity'][$datas['slip_id_field']];

    $output = '<div class="credit-type-container">';

    if ($type == 'deposit') {
        $output .= t('Deposit');
    }elseif ($type == 'withdrawal') {
        $output .= t('Withdrawal');
    }elseif ($type == 'bet') {
        if ($way == 'minus') {
            $output .= t('Bet_Amount');
        }else{
            $output .= t('Bet_Winnings');
        }

        if ($view_type == 'admin_panel') {
            $path = '';
        }else{
            $path = '';
        }

        $output .= '<div><a href="">'.t('Go_to_bet').'</a></div>';
    }

    $output .= '</div>';
    return $output;
}