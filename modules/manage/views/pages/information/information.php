<?php

/** @var TYPE_NAME $type */
/** @var TYPE_NAME $provider */
/** @var TYPE_NAME $prid */

//pr($provider);
set_page_title(t('Informations'));

$form = [
    'submit' => 'User/update_manage_2',
    'name' => 'manage_information_form',
    'hiddens' => [
        'uid' => $uid ?? NULL,
    ],
    'fieldsets' => [
        'informations' => [
            'label' => ''
        ],
    ],
];

$form['fields']['name'] = [
    'label' => t('Name'),
    'type' => 'textfield',
    'values' => $user['name'] ?? '',
    'required' => TRUE,
    'fieldset' => 'informations',
];

$form['fields']['surname'] = [
    'label' => t('Surname'),
    'type' => 'textfield',
    'values' => $user['surname'] ?? '',
    'required' => TRUE,
    'fieldset' => 'informations',
];

$form['fields']['tel'] = [
    'label' => t('Tel'),
    'type' => 'textfield',
    'values' => $user['tel'] ?? '',
    'input' => [
        'attributes' => [
            'class' => ['tel-input'],
        ],
    ],
    'fieldset' => 'informations',
];

$form['fields']['messages'] = [
    'type' => 'markup',
    'value' => '<div class="form-messages"></div>',
];

$form['fields']['submit'] = [
    'type' => 'submit',
    'text' => t('Save'),
    'attributes' => [
        'class' => ['btn','btn-as24'],
    ],
    'fieldset' => 'informations',
];

return form($form);

?>