<?php

/** @var TYPE_NAME $lang */
/** @var TYPE_NAME $user */
/** @var TYPE_NAME $m_path */
/** @var TYPE_NAME $manage_path */

$attrs = [
    'class' => [
        'bet-slip-page',
    ],
    'data-lang' => $lang
];

$mtch = new Matches();

if(isset($m_path[0])){

    $match_id = $m_path[0];
    $match = $mtch->get_full($match_id);
    // pr($match);
    set_page_title($match['home_team']['name'].' - '.$match['away_team']['name']);

    $mtch_detail_ui = new \BetUi\MatchDetail([
        'view_type' => 'front',
        'lang' => $lang,
    ]);

    $attrs['class'][] = 'match-detail-page';
    $attrs['data-list_type'] = 'detail';
    $attrs['data-match_id'] = $match_id;

    $page = $mtch_detail_ui->render($match);

}else{

    $mtch_ui = new \BetUi\Matches([
        'view_type' => 'front',
        'lang' => $lang,
    ]);

    // Todays Matches
    $matches_today = $mtch->get_grouped_matches([
        'from_day' => 0,
        'to_day' => 0
    ]);
    // pr($matches_today);
    $page = $mtch_ui->render_list($matches_today);
    
    // Tomorrow Matches
    $matches_tomorrow = $mtch->get_grouped_matches([
        'from_day' => 1,
        'to_day' => 1
    ]);
    $page .= $mtch_ui->render_list($matches_tomorrow);

    $attrs['class'][] = 'matches-list-page';
    $attrs['data-list_type'] = 'list';

}

$bs = new BetSlip();
$open_slip = $bs->create_open_slip($user['uid']);

$output = '<div' . attrs_output($attrs) . '>';

    $output .= $page;

    if ($view_type == 'front' && $type == 'user') {
        $bet_slip_ui = new BetUi\BetSlip([
            'lang' => $lang,
            'view_type' => 'front',
        ]);

        $slip = $bs->load($open_slip);
        $output .= $bet_slip_ui->render_content($slip);
    }

$output .= '</div>';

return $output;

?>