<?php

$bet_type_ui = new \BetUi\BetType();
$bet_type_model = new BetType();

$list = $bet_type_model->get_all_with_texts(current_lang());

return '
<div id="bet-type-container">
    '.$bet_type_ui->render_bet_type_list($list).'
</div>';

?>