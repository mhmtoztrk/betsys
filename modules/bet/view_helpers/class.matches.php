<?php

namespace BetUi;

class Matches {

    private $lang;
    private $view_type;

    public function __construct($pars = []) {
        $this->lang = $pars['lang'] ?? current_lang();
        $this->view_type = $pars['view_type'] ?? 'front';
    }

    /**
     * Render the match list
     *
     * @param array $grouped_matches Array of grouped matches by league
     * @return string HTML output of the match list
     */
    public function render_list($grouped_matches) {
        $output = '<div class="match-list">';
        foreach ($grouped_matches as $league) {
            $output .= $this->render_list_group($league);
        }
        $output .= '</div>';
        return $output;
    }

    /**
     * Render a match group (league with matches) for the list view
     *
     * @param array $league League and matches data
     * @return string HTML output of the match group
     */
    private function render_list_group($league) {
        $output = '<div class="match-group"><table class="table">';
            $output .= '<thead>';
                $output .= '<tr>';
                    $output .= '<th class="match-group-league-name">' . htmlspecialchars($league['league']['name']) . '</th>';
                    $output .= '<th>1</th>';
                    $output .= '<th>X</th>';
                    $output .= '<th>2</th>';
                $output .= '</tr>';
            $output .= '</thead>';
            $output .= '<tbody>';
            
            foreach ($league['matches'] as $match) {
                $output .= $this->render_list_match_row($match);
            }

            $output .= '</tbody>';
        $output .= '</table></div>';
        return $output;
    }

    /**
     * Render a single match row for the list view
     *
     * @param array $match Match data
     * @return string HTML output of the match row
     */
    private function render_list_match_row($match) {
        // pr($match);
        $mtch = new \Matches();

        $match_id = (int)$match['match_id'];
        $fixture_status = $match['fixture_status'];
        $status = $match['status'];
        $home_score = $match['home_score'] ?? '';
        $away_score = $match['away_score'] ?? '';

        $match_date = '<div class="match-day">'.date('d/m', $match['match_date']).'</div>';
        $match_date .= '<div class="match-hour">'.date('H:i', $match['match_date']).'</div>';

        // Determine time or live info
        if ($status === 'live') {
            $status = \Matches::get_status_data($fixture_status);
            $status_title = $status_data['match_ui'][$this->lang] ?? '';

            $time_info = '<div class="match-status">'.$status_title.'</div>';

            $current_text = '';
            if (!empty($match['current_minute']) && !empty($match['current_second'])) {
                $current_text = '<span class="current-minute">'.$match['current_minute'].'</span>:<span class="current-second">'.$match['current_second'].'</span>';
            }
            $time_info .= '<div class="match-current">'.$current_text.'</div>';
        } else {
            $time_info = $match_date;
        }

        $output = '<tr id="mr-' . $match_id . '" class="match-row" data-match_id="' . $match_id . '">';

        // Team names (Clickable for match detail)
            $output .= '<td class="match-data">';
                $output .= '<a href="'.BASE_URL.'/manage/matches/' . $match_id . '">';

                    $output .= '<div class="match-time">' . $time_info . '</div>';
                    $output .= '<div class="teams">';
                        $output .= $this->render_list_team($match['home_team'], $home_score);
                        $output .= $this->render_list_team($match['away_team'], $away_score);
                    $output .= '</div>';

                $output .= '</a>';
            $output .= '</td>';

            // Odds (1, X, 2)
            $bet_type_id = 1; // Match Winner
            $odds = $match['odds'][$bet_type_id] ?? [];

            $odd_pars = [
                'odds' => $odds,
                'bet_typ_id' => $bet_type_id,
                'match_id' => $match_id,
            ];

            // Home Win
            $odd_pars['bet_value'] = 'Home';
            $output .= $this->render_list_odd_td($odd_pars);

            // Away Win
            $odd_pars['bet_value'] = 'Draw';
            $output .= $this->render_list_odd_td($odd_pars);

            // Away Win
            $odd_pars['bet_value'] = 'Away';
            $output .= $this->render_list_odd_td($odd_pars);

        $output .= '</tr>';

        return $output;
    }

    public function render_list_team($team, $score){
        $name = htmlspecialchars($team['name']);
        $output = '<div class="list-team list-team-'.$team['id'].'">';

            $output .= '<div class="list-team-left">';
                $output .= '<div class="list-team-logo"><img src="/'.$team['logo_icon_path'].'" alt="'.$name.'"/></div>';
                $output .= '<div class="list-team-name">'.$name.'</div>';
            $output .= '</div>';

            $output .= '<div class="list-team-score">'.$score.'</div>';

        $output .= '</div>';

        return $output;
    }

    public function render_list_odd_td($pars){

        $odds = $pars['odds'];
        $bet_value = $pars['bet_value'];

        $attrs = [
            'class' => [
                'bet-option',
            ],
            'data-match_id' => $pars['match_id'],
            'data-bet_type_id' => $pars['bet_typ_id'],
            'data-bet_value' => $bet_value,
            'data-odd_value' => $odds[$bet_value]['odd_value'] ?? 1,
        ];

        if($this->view_type == 'front') $attrs['class'][] = 'bet-create';

        $output = '<td class="odd-td"><div'.attrs_output($attrs).'>';
            $output .= isset($odds[$bet_value]) ? $odds[$bet_value]['odd_value'] : '-';
        $output .= '</div></td>';

        return $output;

    }
}


?>
