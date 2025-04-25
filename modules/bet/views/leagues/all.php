<?php

$league_ui = new \BetUi\League();
$league_model = new League();

$list = $league_model->all_grouped_by_country();

return '
<div class="league-filter">
    <input type="text" id="country_filter" class="league-input" placeholder="Country">
    <input type="text" id="league_filter" class="league-input" placeholder="League">
</div>
<div id="league_container">
    '.$league_ui->render_league_list($list).'
</div>';

?>