<?php

/** @var TYPE_NAME $user */
/** @var TYPE_NAME $view_type */

set_page_title(t('Account'));


$form = [
    'name' => 'account_edit_form',
    'submit' => 'user/account_form_submit',
    'hiddens' => [
        'uid' => $user['uid'],
    ],
    'fieldsets' => [
        'main' => [
            'label' => '',
        ],
    ],
];

if ($view_type == 'front'){
    $form['fields']['markup'] =[
        'type' => 'markup',
        'value' => alert(t('Please_enter_current_pass_to_edit_account'), 'warning'),
        'fieldset' => 'main',
    ];

    $form['fields']['current_pass'] = [
        'label' => t('Current_Password'),
        'required' => TRUE,
        'type' => 'password',
        'validate' => [
            [
                'function' => 'User/is_current_pass',
                'message' => t('Current_Pass_Wrong'),
                'vars' => [
                    'uid' => $user['uid'],
                ],
            ],
        ],
        'fieldset' => 'main',
    ];

}

$form['fields']['username'] = [
    'label' => t('Username'),
    'type' => 'textfield',
    'values' => $user['username'],
    'required' => TRUE,
    'validate' => [
        [
            'function' => 'User/is_username_available',
            'message' => t('This_username_is_already_used'),
            'vars' => [
                'uid' => $user['uid'] ?? 0,
            ],
        ],
    ],
    'fieldset' => 'main',
];

$form['fields']['mail'] = [
    'label' => t('E_Mail'),
    'type' => 'textfield',
    'values' => $user['mail'],
    'validate' => [
        [
            'function' => 'User/is_mail_available',
            'message' => t('This_mail_is_already_used'),
            'vars' => [
                'uid' => $user['uid'] ?? 0,
            ],
        ],
        [
            'function' => 'mail_validate',
            'message' => t('Please_enter_a_valid_email'),
        ],
    ],
    'fieldset' => 'main',
];

$form['fields']['pass'] = [
    'label' => t('Password'),
    'type' => 'password',
    'fieldset' => 'main',
];

$form['fields']['messages'] = [
    'type' => 'markup',
    'value' => '<div class="form-messages"></div>',
];

$form['fields']['submit'] = [
    'type' => 'submit',
    'text' => t('Save'),
    'attributes' => [
        'class' => ['btn','btn-as24']
    ],
    'fieldset' => 'main',
];

$output = '<div class="main-container">';
    $output .= form($form);
$output .= '</div>';

return $output;

?>
