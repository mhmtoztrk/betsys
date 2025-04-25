<?php

require 'class.cron.php';

add_routing([
    'path' => [
        0 => 'cr',
    ],
    'run' => 'cron/cr',
]);

function add_cron_key($key, $pars){

    $json_file = CUSTOM_MODULES.'/cron/crons.json';
    $json = file_get_contents($json_file);

    $crons = json_decode(file_get_contents($json_file), 1);
    
    $crons[$key] = $pars;

    $df = new Df();
    $df->set(json_encode($crons), $json_file);

}