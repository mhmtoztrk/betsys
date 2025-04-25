<?php

namespace BetUi;

class MatchDetail {

    private $lang;
    private $view_type;

    public function __construct($pars = []) {
        $this->lang = $pars['lang'] ?? current_lang();
        $this->view_type = $pars['view_type'] ?? 'front';
    }

    /**
     * Render the entire match detail page
     * @param array $match Match data
     * @return string HTML output
     */
    public function render($match) {
        // pr($match);
        $output = '<div class="match-detail">';
            $output .= $this->render_header($match);
            $output .= $this->render_tabs($match);
        $output .= '</div>';
        return $output;
    }

    /**
     * Render the match header section (teams, time, score, status)
     *
     * @param array $match Match data
     * @return string HTML output
     */
    public function render_header($match) {
        $match_id = (int)$match['match_id'];
        $home = $match['home_team'];
        $away = $match['away_team'];
        $home_score = $match['home_score'] ?? '';
        $away_score = $match['away_score'] ?? '';
        $fixture_status = $match['fixture_status'];
        $status = $match['status'];

        $output = '<div class="match-header" id="match-header-' . $match_id . '">';

        // Match Time
        if ($status === 'live') {
            $status_data = \Matches::get_status_data($fixture_status);
            $status_title = $status_data['match_ui'][$this->lang] ?? '';
            $current_min = str_pad((int)$match['current_minute'], 2, '0', STR_PAD_LEFT);
            $current_sec = str_pad((int)$match['current_second'], 2, '0', STR_PAD_LEFT);

            $time_html  = '<div class="match-status">' . $status_title . '</div>';
            $time_html .= '<div class="match-current-time">' . $current_min . ':' . $current_sec . '</div>';
        } else {
            $time_html  = '<div class="match-date">' . date('d/m', $match['match_date']) . '</div>';
            $time_html .= '<div class="match-hour">' . date('H:i', $match['match_date']) . '</div>';
        }

        // Teams and Score
        $output .= '<div class="match-header-teams">';
            $output .= '<div class="match-header-team match-header-home">';
                $output .= '<img src="/' . $home['logo_icon_path'] . '" alt="' . htmlspecialchars($home['name']) . '">';
                $output .= '<span>' . htmlspecialchars($home['name']) . '</span>';
            $output .= '</div>';

            $output .= '<div class="match-header-score">';
                $output .= $home_score . ' - ' . $away_score;
            $output .= '</div>';

            $output .= '<div class="match-header-team match-header-away">';
                $output .= '<img src="/' . $away['logo_icon_path'] . '" alt="' . htmlspecialchars($away['name']) . '">';
                $output .= '<span>' . htmlspecialchars($away['name']) . '</span>';
            $output .= '</div>';
        $output .= '</div>';

        // Time Info
        $output .= '<div class="match-header-time">' . $time_html . '</div>';

        $output .= '</div>';
        return $output;
    }

    /**
     * Render the match score
     * @param array $match Match data
     * @return string HTML output
     */
    private function render_score($match) {
        $score = $match['home_score'] . ' - ' . $match['away_score'];
        return '<div class="match-score">' . $score . '</div>';
    }

    /**
     * Render the tab navigation
     * @param array $match Match data
     * @return string HTML output
     */
    private function render_tabs($match) {
        $output = '<div class="match-tabs"><div class="tab-buttons">';
            $output .= '<button class="tab-button active" data-tab="odds">'.t('Odds').'</button>';
            $output .= '<button class="tab-button" data-tab="stats">'.t('Stats').'</button>';
        $output .= '</div></div>';

        $output .= '<div id="tab-odds" class="tab-content active">';
            $output .= $this->render_odds($match);
        $output .= '</div>';

        $output .= '<div id="tab-stats" class="tab-content">';
            $output .= $this->render_stats($match['stats']);
        $output .= '</div>';

        return $output;
    }

    /**
     * Render odds tab content
     * @param array $match Match data
     * @return string HTML output
     */
    public function render_odds($match) {
        $output = '<div class="match-odds">';
    
        // Get all bet types with localized texts
        $bet_type_model = new \BetType();
        $bet_types = $bet_type_model->get_all_with_texts($this->lang, 'active');
    
        $match_odds = $match['odds'] ?? [];
    
        foreach ($bet_types as $type) {
            $bet_type_id = $type['bet_type_id'];
            $odds_data = $match_odds[$bet_type_id] ?? null;
    
            if (!$odds_data) continue; // Skip if no odds for this type
    
            $title = htmlspecialchars($type['texts']['name'] ?? $type['api_key']);
    
            $output .= '<div class="bet-group bet-type-'.$bet_type_id.'" data-bet_type="'.$bet_type_id.'">';
                $output .= '<div class="bet-group-title">' . $title . '</div>';
                $output .= '<div class="bet-options">';
    
                foreach ($odds_data as $value => $odd) {
                    $output .= $this->render_odd([
                        'match_id'     => $match['match_id'],
                        'bet_type_id'  => $bet_type_id,
                        'value'        => $value,
                        'odd_value'    => $odd['odd_value'],
                        'suspended'    => $odd['suspended'] ?? 0
                    ]);
                }
    
                $output .= '</div>';
            $output .= '</div>';
        }
    
        $output .= '</div>';
        return $output;
    }    

    /**
     * Render a single odd element
     * @param string $value Odd value
     * @param array $odd Odd data
     * @return string HTML output
     */
    public function render_odd($pars) {
        $classes = ['bet-option'];
    
        if ($this->view_type === 'front') {
            $classes[] = 'bet-create';
        }
    
        if (!empty($pars['suspended'])) {
            $classes[] = 'suspended';
        }
    
        $attrs = [
            'class'            => implode(' ', $classes),
            'data-match_id'    => $pars['match_id'],
            'data-bet_type_id'    => $pars['bet_type_id'],
            'data-bet_value'   => $pars['value'],
            'data-odd_value'   => $pars['odd_value'],
        ];
    
        // Cleaned odd value and label
        $odd_label = htmlspecialchars($pars['value'] ?? '-');
        $odd_value = $pars['odd_value'] ?? '-';
    
        $output = '<div ' . attrs_output($attrs) . '>';
            $output .= '<div class="odd-label">' . $odd_label . '</div>';
            $output .= '<div class="odd-value">' . $odd_value . '</div>';
        $output .= '</div>';
    
        return $output;
    }

    /**
     * Render match statistics
     *
     * @param int $fixture_id
     * @return string
     */
    public function render_stats($stats) {

        if (empty($stats)) {
            return '<div class="no-stats">'.t('No_Stats_Available').'</div>';
        }

        $stat_types = \Matches::stat_types();

        $output = '<div class="match-stats">';

        foreach ($stat_types as $type_key => $stat_type) {
            if (isset($stats[$type_key])) {

                $left = isset($stats[$type_key][0]) ? $stats[$type_key][0] : '-';
                $right = isset($stats[$type_key][1]) ? $stats[$type_key][1] : '-';

                $output .= '<div class="stat-row">';
    
                    $output .= '<div class="stat-value stat-left">'.$left.'</div>';
                    $output .= '<div class="stat-label">'.$stat_type[$this->lang].'</div>';
                    $output .= '<div class="stat-value stat-right">'.$right.'</div>';

    
                $output .= '</div>';
            }
        }

        $output .= '</div>';

        return $output;

    }

}

?>
