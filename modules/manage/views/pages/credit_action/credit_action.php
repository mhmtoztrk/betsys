<?php

/** @var TYPE_NAME $user */
/** @var TYPE_NAME $view_type */

$user_ui = new \BetUi\User();

$form = [
    'submit' => 'Credits/credit_action_submit',
    'validate' => 'Credits/credit_action_validate',
    'name' => 'credit_action_form',
    'hiddens' => [
        'user' => $user,
    ],
];

$form['fields']['type'] = [
    'label' => 'Type',
    'type' => 'options',
    'rows' => [
        'add' => t('Add').' (+)',
        'subtract' => t('Subtract').' (-)',
    ],
    'required' => TRUE,
    'values' => 'add',
];

$form['fields']['amount'] = [
    'label' => t('Amount'),
    'type' => 'number',
    'step' => 0.01,
    'required' => TRUE,
    'max_length' => 11,
    'values' => '',
];

$form['fields']['note'] = [
    'label' => t('Note'),
    'type' => 'textfield',
    'maxlength' => 127,
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

return $user_ui->balance_alert($user['balance']).form($form);


?>