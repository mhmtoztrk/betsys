<?php

$datas = [
    'type' => 'manage',
    'entity_type' => 'project',
    'path' => $manage_path,
    'export_title' => t('Projects'),
    'def_order' => 'p__prid__desc',
    'main_table' => [
        'table' => 'projects',
        'primary_key' => 'prid',
        'alias' => 'p',
        'fields' => ['prid','uid','name','created_at'],
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
        'empty_text' => def_empty('project'),
        'limit' => 50,
        'table_attributes' => [
            'class' => ['table-striped']
        ],
        'add_new' => [
            'text' => t('Create'),
            'list' => [
                $manage_path.'/create' => t('New'),
            ],
        ],
        'cols' => [
            [
                'label' => t('Prid'),
                'settings' => [
                    'type' => 'custom',
                    'output' => '#[p_prid]',
                ],
            ],
            [
                'label' => 'Name',
                'settings' => [
                    'type' => 'standard',
                    'field' => 'p_name',
                    'link' => BASE_PATH.'/'.PANEL_PATH.'/project/[p_prid]/project_dashboard',
                ],
            ],
            [
                'label' => t('Created'),
                'settings' => [
                    'type' => 'time',
                    'field' => 'p_created_at',
                ],
            ],
            [
                'label' => t('Actions'),
                'settings' => [
                    'type' => 'action',
                    'action_type' => 'project_manage',
                    'id_field' => 'p_prid',
                    'uid_field' => 'p_uid',
                ],
            ],
        ],
    ],
];

return advanced_table_view($datas);

?>