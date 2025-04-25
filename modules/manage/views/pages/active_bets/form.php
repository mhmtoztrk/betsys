<?php

$form = [
    'submit' => 'Project/manage_form_submit',
    'name' => 'manage_form_project',
];

if (isset($project)) $form['hiddens']['prid'] = $project['prid'];
$form['hiddens']['manage_path'] = $manage_path;

$form['hiddens']['uid'] = $user['uid'];

$form['fields']['name'] = [
    'label' => 'Project Name',
    'type' => 'textfield',
    'required' => TRUE,
    'max_length' => 70,
    'values' => $project['name'] ?? '',
    'fieldset' => 'main',
];

$form['fields']['description'] = [
    'label' => t('Description'),
    'type' => 'textarea',
    'values' => $project['description'] ?? '',
    'fieldset' => 'main',
];

if(!isset($project)) {

    $form['fields']['main_app'] = [
        'label' => 'Main App Name',
        'type' => 'textfield',
        'required' => TRUE,
        'max_length' => 70,
        'values' => '',
        'fieldset' => 'main',
    ];

}

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

return form($form);


?>