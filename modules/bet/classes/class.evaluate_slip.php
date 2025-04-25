<?php

class EvaluateSlip {

    private $match;
    private $home_score;
    private $away_score;
    private $fixture_status;
    private $status;

    public function __construct($match) {
        $this->match = $match;
        $this->home_score = (int)($match['home_score'] ?? 0);
        $this->away_score = (int)($match['away_score'] ?? 0);
        $this->fixture_status = $match['fixture_status'] ?? '';
        $this->status = $match['status'] ?? '';
    }

    /**
     * Checks the result of a single bet
     *
     * @param int $bet_type_id
     * @param array $bet
     * @return string ('won' or 'lost')
     */
    public function check($bet_type_id, $bet) {
        $api_key = BetType::get_api_key_by_id($bet_type_id);
        $method = $this->get_check_method($api_key);

        if (method_exists($this, $method)) {
            return $this->$method($bet);
        }

        // Default: if no handler found, consider lost
        return 'lost';
    }

    /**
     * Maps api_key to internal check method name
     */
    private function get_check_method($api_key) {
        $map = [
            'Match Winner' => 'check_match_winner',
            'Home/Away' => 'check_home_away',
            'Second Half Winner' => 'check_second_half_winner',
            'First Half Winner' => 'check_first_half_winner',
            'HT/FT Double' => 'check_ht_ft',
            'Goals Over/Under' => 'check_goals_over_under',
            'Goals Over/Under First Half' => 'check_goals_over_under_first_half',
            'Goals Over/Under - Second Half' => 'check_goals_over_under_second_half',
            'Odd/Even' => 'check_odd_even',
            'Correct Score' => 'check_exact_score',
            'Correct Score - First Half' => 'check_exact_score_first_half',
            'Correct Score - Second Half' => 'check_exact_score_second_half',
            'Double Chance' => 'check_double_chance',
            'Both Teams Score' => 'check_both_teams_score',
            'Handicap Result' => 'check_handicap_result',
            'Asian Handicap' => 'check_asian_handicap',
            'Asian Handicap First Half' => 'check_asian_handicap_first_half',
            'Clean Sheet - Home' => 'check_clean_sheet_home',
            'Clean Sheet - Away' => 'check_clean_sheet_away',
            'Win to Nil - Home' => 'check_win_to_nil_home',
            'Win to Nil - Away' => 'check_win_to_nil_away',
            'Team To Score First' => 'check_team_score_first',
            'Team To Score Last' => 'check_team_score_last',
            'Odd/Even - First Half' => 'check_odd_even_first_half',
            'Odd/Even - Second Half' => 'check_odd_even_second_half',
            'Win Both Halves' => 'check_win_both_halves',
            'Draw No Bet (1st Half)' => 'check_draw_no_bet_first_half',
            'Draw No Bet (2nd Half)' => 'check_draw_no_bet_second_half',
            // ... diğerleri de var ama şimdilik temel türler
        ];

        return $map[$api_key] ?? null;
    }

    // ---------- CHECK METHODS (her biri ayrı) ----------

    private function check_match_winner($bet) {
        if ($this->home_score > $this->away_score && $bet['bet_value'] == 'Home') return 'won';
        if ($this->home_score == $this->away_score && $bet['bet_value'] == 'Draw') return 'won';
        if ($this->home_score < $this->away_score && $bet['bet_value'] == 'Away') return 'won';
        return 'lost';
    }

    private function check_home_away($bet) {
        if ($this->home_score > $this->away_score && $bet['bet_value'] == 'Home') return 'won';
        if ($this->away_score > $this->home_score && $bet['bet_value'] == 'Away') return 'won';
        return 'lost';
    }

    private function check_first_half_winner($bet) {
        // ilk yarı skorunu almak gerekirdi ama şimdilik tam veri yok, varsayılan 'lost'
        return 'lost';
    }

    private function check_second_half_winner($bet) {
        return 'lost';
    }

    private function check_goals_over_under($bet) {
        $total_goals = $this->home_score + $this->away_score;
        $handicap = $this->parse_handicap($bet['bet_value']);
        if (strpos($bet['bet_value'], 'Over') !== false) {
            return ($total_goals > $handicap) ? 'won' : 'lost';
        } else {
            return ($total_goals < $handicap) ? 'won' : 'lost';
        }
    }

    private function check_goals_over_under_first_half($bet) {
        return 'lost'; // detaylı veri yok
    }

    private function check_goals_over_under_second_half($bet) {
        return 'lost'; // detaylı veri yok
    }

    private function check_odd_even($bet) {
        $total_goals = $this->home_score + $this->away_score;
        if (($total_goals % 2 == 0 && $bet['bet_value'] == 'Even') ||
            ($total_goals % 2 == 1 && $bet['bet_value'] == 'Odd')) {
            return 'won';
        }
        return 'lost';
    }

    private function check_exact_score($bet) {
        $score = $bet['bet_value'];
        list($h, $a) = explode(':', $score);
        if ((int)$h === $this->home_score && (int)$a === $this->away_score) {
            return 'won';
        }
        return 'lost';
    }

    private function check_ht_ft($bet) {
        return 'lost';
    }

    private function check_double_chance($bet) {
        if (in_array($bet['bet_value'], ['Home/Draw', 'Draw/Home'])) {
            if ($this->home_score >= $this->away_score) return 'won';
        } elseif (in_array($bet['bet_value'], ['Away/Draw', 'Draw/Away'])) {
            if ($this->away_score >= $this->home_score) return 'won';
        } elseif ($bet['bet_value'] == 'Home/Away') {
            if ($this->home_score != $this->away_score) return 'won';
        }
        return 'lost';
    }

    private function check_both_teams_score($bet) {
        $both = ($this->home_score > 0 && $this->away_score > 0);
        if (($both && $bet['bet_value'] == 'Yes') || (!$both && $bet['bet_value'] == 'No')) {
            return 'won';
        }
        return 'lost';
    }

    private function check_handicap_result($bet) {
        return 'lost';
    }

    private function check_asian_handicap($bet) {
        return 'lost';
    }

    private function check_asian_handicap_first_half($bet) {
        return 'lost';
    }

    private function check_clean_sheet_home($bet) {
        if ($this->away_score == 0 && $bet['bet_value'] == 'Yes') return 'won';
        if ($this->away_score > 0 && $bet['bet_value'] == 'No') return 'won';
        return 'lost';
    }

    private function check_clean_sheet_away($bet) {
        if ($this->home_score == 0 && $bet['bet_value'] == 'Yes') return 'won';
        if ($this->home_score > 0 && $bet['bet_value'] == 'No') return 'won';
        return 'lost';
    }

    private function check_win_to_nil_home($bet) {
        if ($this->away_score == 0 && $this->home_score > 0) return 'won';
        return 'lost';
    }

    private function check_win_to_nil_away($bet) {
        if ($this->home_score == 0 && $this->away_score > 0) return 'won';
        return 'lost';
    }

    private function check_team_score_first($bet) {
        return 'lost';
    }

    private function check_team_score_last($bet) {
        return 'lost';
    }

    private function check_odd_even_first_half($bet) {
        return 'lost';
    }

    private function check_odd_even_second_half($bet) {
        return 'lost';
    }

    private function check_win_both_halves($bet) {
        return 'lost';
    }

    private function check_draw_no_bet_first_half($bet) {
        return 'lost';
    }

    private function check_draw_no_bet_second_half($bet) {
        return 'lost';
    }

    // ---------- HELPER METHODS ----------

    private function parse_handicap($value) {
        if (preg_match('/(\d+\.?\d*)/', $value, $matches)) {
            return (float)$matches[1];
        }
        return 0;
    }

}

?>
