<?php

$league_ui = new \BetUi\League();
$league_model = new League();

$list = $league_model->all_grouped_by_country(['status' => 'active']);
return $league_ui->render_league_list($list);

?>
