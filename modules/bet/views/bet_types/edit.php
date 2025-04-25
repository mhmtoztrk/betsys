<?php

$pars = get_content_pars();
$bet_type = $pars['bet_type'];

$bet_type_ui = new \BetUi\BetType();

$output = '<div class="main-container">';
    $output .= $bet_type_ui->edit_form($bet_type);
$output .= '</div>';

return $output;

?>