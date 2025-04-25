<?php

/** @var TYPE_NAME $user */
/** @var TYPE_NAME $m_path */
/** @var TYPE_NAME $manage_path */

$user_ui = new \BetUi\User();

$datas = [
    'type' => 'manage',
    'entity_type' => 'credit_activity',
    'path' => $manage_path,
    'export_title' => t('Credit_Activities'),
    'def_order' => 'p__crid__desc',
    'main_table' => [
        'table' => 'credit_actions',
        'primary_key' => 'crid',
        'alias' => 'p',
        'fields' => ['crid','uid','amount','type','credit_way','slip_id','note','created_at'],
    ],
    'conditions' => [
        [
            'sh' => 'p',
            'key' => 'uid',
            'type' => '==',
            'value' => $user['uid'],
        ],
    ],
    'view' => [
        'pager' => TRUE,
        'summary' => TRUE,
        'export' => FALSE,
        'bulk_operations' => FALSE,
        'empty_text' => def_empty('credit_activity'),
        'limit' => 50,
        'table_attributes' => [
            'class' => ['table-striped']
        ],
        'class_prefixes' => [
            'p_credit_way' => 'credit',
        ],
        'cols' => [
            [
                'label' => t('Amount'),
                'settings' => [
                    'type' => 'amount',
                    'amount_field' => 'p_amount',
                ],
            ],
            [
                'label' => t('Type'),
                'settings' => [
                    'type' => 'credit_type',
                    'type_field' => 'p_type',
                    'way_field' => 'p_credit_way',
                    'slip_id_field' => 'p_slip_id',
                    'view_type' => $view_type,
                    'uid' => $user['uid'],
                ],
            ],
            [
                'label' => t('Note'),
                'settings' => [
                    'type' => 'standard',
                    'field' => 'p_note',
                ],
            ],
            [
                'label' => t('Date'),
                'settings' => [
                    'type' => 'time',
                    'field' => 'p_created_at',
                ],
            ],
        ],
    ],
];

return $user_ui->balance_alert($user['balance']).advanced_table_view($datas);

?>