<?php

class EvaluateSlip {
    private $match;

    public function __construct($match) {
        $this->match = $match;
    }

    public function check($bet_type_id, $bet) {

        if (method_exists($this, $bet_type_id)) {
            return $this->$bet_type_id($bet);
        }

        return 'method_not_exist';
    }

    public function is_fields_empty(...$fields) {
        foreach ($fields as $f) {
            if (empty($f) && $f !== '0' && $f !== 0) return TRUE;
        }
        return FALSE;
    }

    // 1X2 - xx (Live) Minutes Helper
    public function m_1x2($bet, $before) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'], $this->match['home_team_id'], $this->match['away_team_id'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value']));
        $home_id = $this->match['home_team_id'];
        $away_id = $this->match['away_team_id'];

        $home_goals = 0;
        $away_goals = 0;

        foreach ($this->match['events'] as $event) {
            if ($event['type'] === 'goal') {
                $minute = (int)($event['minute'] ?? 0);
                if ($minute <= $before) {
                    if ($event['team_id'] == $home_id) $home_goals++;
                    elseif ($event['team_id'] == $away_id) $away_goals++;
                }
            }
        }

        if ($home_goals > $away_goals && $value === 'home') return 'won';
        if ($home_goals < $away_goals && $value === 'away') return 'won';
        if ($home_goals === $away_goals && $value === 'draw') return 'won';

        return 'lost';
    }

    // Generic method for "Method of Nth Goal" type bets
    public function m_goal_method($bet, $goal_index) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value']));
        $goal_events = [];

        foreach ($this->match['events'] as $event) {
            if ($event['type'] === 'goal') {
                $goal_events[] = $event;
                if (count($goal_events) > $goal_index) break;
            }
        }

        if (count($goal_events) <= $goal_index) {
            return ($value === 'no goal') ? 'won' : 'lost';
        }

        $detail = strtolower($goal_events[$goal_index]['detail'] ?? '');

        $map = [
            'normal_goal' => 'shot',
            'header' => 'header',
            'penalty' => 'penalty',
            'free_kick' => 'free kick',
            'own_goal' => 'own goal'
        ];

        $method = $map[$detail] ?? null;

        return ($value === strtolower($method)) ? 'won' : 'lost';
    }

    //Match Winner
    public function p001($bet) {
        if ($this->is_fields_empty($this->match['home_score'],$this->match['away_score'],$bet['bet_value'])) return 'fields_empty';

        $home = $this->match['home_score'];
        $away = $this->match['away_score'];
        $value = strtolower($bet['bet_value']);

        if ($value === 'home' && $home > $away) return 'won';
        if ($value === 'away' && $away > $home) return 'won';
        if ($value === 'draw' && $home == $away) return 'won';

        return 'lost';
    }

    // Home/Away
    public function p002($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $this->match['away_score'], $bet['bet_value'])) return 'fields_empty';

        $home = $this->match['home_score'];
        $away = $this->match['away_score'];
        $value = strtolower($bet['bet_value']);

        if ($value === 'home' && $home > $away) return 'won';
        if ($value === 'away' && $away > $home) return 'won';

        return 'lost';
    }

    // Second Half Winner
    public function p003($bet) {
        if ($this->is_fields_empty(
            $this->match['home_score'], $this->match['away_score'],
            $this->match['halftime_home_score'], $this->match['halftime_away_score'],
            $bet['bet_value']
        )) return 'fields_empty';

        $home_2h = $this->match['home_score'] - $this->match['halftime_home_score'];
        $away_2h = $this->match['away_score'] - $this->match['halftime_away_score'];
        $value = strtolower($bet['bet_value']);

        if ($value === 'home' && $home_2h > $away_2h) return 'won';
        if ($value === 'away' && $away_2h > $home_2h) return 'won';
        if ($value === 'draw' && $home_2h === $away_2h) return 'won';

        return 'lost';
    }

    // Goals Over/Under
    public function p005($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $total_goals = $this->match['home_score'] + $this->match['away_score'];
        $value = strtolower($bet['bet_value']); // örn: over 2.5

        if (preg_match('/(over|under)\s+([\d.]+)/', $value, $matches)) {
            $type = $matches[1]; // 'over' veya 'under'
            $threshold = floatval($matches[2]); // örn: 2.5

            if ($type === 'over' && $total_goals > $threshold) return 'won';
            if ($type === 'under' && $total_goals < $threshold) return 'won';

            return 'lost';
        }

        return 'method_not_exist';
    }

    // Goals Over/Under First Half
    public function p006($bet) {
        if ($this->is_fields_empty(
            $this->match['halftime_home_score'],
            $this->match['halftime_away_score'],
            $bet['bet_value']
        )) return 'fields_empty';

        $total_goals_ht = $this->match['halftime_home_score'] + $this->match['halftime_away_score'];
        $value = strtolower($bet['bet_value']); // örn: "over 1.5"

        if (preg_match('/(over|under)\s+([\d.]+)/', $value, $matches)) {
            $type = $matches[1]; // 'over' veya 'under'
            $threshold = floatval($matches[2]);

            if ($type === 'over' && $total_goals_ht > $threshold) return 'won';
            if ($type === 'under' && $total_goals_ht < $threshold) return 'won';

            return 'lost';
        }

        return 'method_not_exist';
    }

    // HT/FT Double
    public function p007($bet) {
        if ($this->is_fields_empty(
            $this->match['halftime_home_score'], $this->match['halftime_away_score'],
            $this->match['home_score'], $this->match['away_score'],
            $bet['bet_value']
        )) return 'fields_empty';

        $ht_home = $this->match['halftime_home_score'];
        $ht_away = $this->match['halftime_away_score'];
        $ft_home = $this->match['home_score'];
        $ft_away = $this->match['away_score'];

        $value = strtolower($bet['bet_value']); // örn: home/draw

        $ht_result = $ht_home > $ht_away ? 'home' : ($ht_home < $ht_away ? 'away' : 'draw');
        $ft_result = $ft_home > $ft_away ? 'home' : ($ft_home < $ft_away ? 'away' : 'draw');

        if ($value === "$ht_result/$ft_result") {
            return 'won';
        }

        return 'lost';
    }

    // Both Teams Score
    public function p008($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = $this->match['home_score'];
        $away = $this->match['away_score'];
        $value = strtolower($bet['bet_value']);

        if ($value === 'yes' && $home > 0 && $away > 0) return 'won';
        if ($value === 'no' && ($home == 0 || $away == 0)) return 'won';

        return 'lost';
    }

    // Handicap Result
    public function p009($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = $this->match['home_score'];
        $away = $this->match['away_score'];
        $value = strtolower($bet['bet_value']); // örn: "home -1"

        if (preg_match('/^(home|away|draw)\s*([+-]?\d+)$/', $value, $matches)) {
            $type = $matches[1]; // home, away, draw
            $handicap = intval($matches[2]);

            // handikapı uygulayarak yeni skor hesaplama
            $adjusted_home = $home;
            $adjusted_away = $away;

            if ($type === 'home' || $type === 'draw') {
                $adjusted_home += $handicap;
            } else if ($type === 'away') {
                $adjusted_away += $handicap;
            }

            if ($adjusted_home > $adjusted_away && $type === 'home') return 'won';
            if ($adjusted_away > $adjusted_home && $type === 'away') return 'won';
            if ($adjusted_home === $adjusted_away && $type === 'draw') return 'won';

            return 'lost';
        }

        return 'method_not_exist';
    }

    // Exact Score
    public function p010($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = $this->match['home_score'];
        $away = $this->match['away_score'];
        $value = trim($bet['bet_value']); // örn: "2:1"

        if (!preg_match('/^\d+:\d+$/', $value)) return 'method_not_exist';

        list($target_home, $target_away) = explode(':', $value);

        if ((int)$home === (int)$target_home && (int)$away === (int)$target_away) {
            return 'won';
        }

        return 'lost';
    }

    // Highest Scoring Half
    public function p011($bet) {
        if ($this->is_fields_empty(
            $this->match['home_score'], $this->match['away_score'],
            $this->match['halftime_home_score'], $this->match['halftime_away_score'],
            $bet['bet_value']
        )) return 'fields_empty';

        $ht_goals = $this->match['halftime_home_score'] + $this->match['halftime_away_score'];
        $ft_goals = $this->match['home_score'] + $this->match['away_score'];
        $sh_goals = $ft_goals - $ht_goals;

        $value = strtolower(trim($bet['bet_value'])); // "draw", "1st half", "2nd half"

        if ($ht_goals === $sh_goals && $value === 'draw') return 'won';
        if ($ht_goals > $sh_goals && $value === '1st half') return 'won';
        if ($sh_goals > $ht_goals && $value === '2nd half') return 'won';

        return 'lost';
    }

    // Double Chance
    public function p012($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = $this->match['home_score'];
        $away = $this->match['away_score'];
        $value = strtolower($bet['bet_value']); // örn: "home/draw"

        $result = $home > $away ? 'home' : ($home < $away ? 'away' : 'draw');

        if (strpos($value, $result) !== false) return 'won';

        return 'lost';
    }

    // First Half Winner
    public function p013($bet) {
        if ($this->is_fields_empty($this->match['halftime_home_score'], $this->match['halftime_away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = $this->match['halftime_home_score'];
        $away = $this->match['halftime_away_score'];
        $value = strtolower($bet['bet_value']); // "home", "draw", "away"

        if ($value === 'home' && $home > $away) return 'won';
        if ($value === 'away' && $away > $home) return 'won';
        if ($value === 'draw' && $home === $away) return 'won';

        return 'lost';
    }

    // Team To Score First
    public function p014($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'], $this->match['home_team_id'], $this->match['away_team_id'])) {
            return 'fields_empty';
        }

        $events = $this->match['events'];
        $value = strtolower($bet['bet_value']);

        $first_goal_team_id = null;

        foreach ($events as $event) {
            if (isset($event['type']) && $event['type'] === 'goal') {
                $first_goal_team_id = $event['team_id'];
                break;
            }
        }

        if ($first_goal_team_id === null) {
            // Gol olmadıysa sadece "draw" kazanır
            return $value === 'draw' ? 'won' : 'lost';
        }

        if (
            ($value === 'home' && $first_goal_team_id == $this->match['home_team_id']) ||
            ($value === 'away' && $first_goal_team_id == $this->match['away_team_id'])
        ) {
            return 'won';
        }

        return 'lost';
    }

    // Team To Score Last
    public function p015($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'], $this->match['home_team_id'], $this->match['away_team_id'])) {
            return 'fields_empty';
        }

        $events = $this->match['events'];
        $value = strtolower($bet['bet_value']);

        $last_goal_team_id = null;

        // sondan başlayarak son golü bul
        for ($i = count($events) - 1; $i >= 0; $i--) {
            if (isset($events[$i]['type']) && $events[$i]['type'] === 'goal') {
                $last_goal_team_id = $events[$i]['team_id'];
                break;
            }
        }

        if ($last_goal_team_id === null) {
            // Gol yoksa sadece "draw" kazanır
            return $value === 'draw' ? 'won' : 'lost';
        }

        if (
            ($value === 'home' && $last_goal_team_id == $this->match['home_team_id']) ||
            ($value === 'away' && $last_goal_team_id == $this->match['away_team_id'])
        ) {
            return 'won';
        }

        return 'lost';
    }

    // Total - Home
    public function p016($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home_goals = $this->match['home_score'];
        $value = strtolower($bet['bet_value']); // örn: "over 1.5"

        if (preg_match('/(over|under)\s+([\d.]+)/', $value, $matches)) {
            $type = $matches[1]; // 'over' or 'under'
            $threshold = floatval($matches[2]);

            if ($type === 'over' && $home_goals > $threshold) return 'won';
            if ($type === 'under' && $home_goals < $threshold) return 'won';

            return 'lost';
        }

        return 'method_not_exist';
    }

    // Total - Away
    public function p017($bet) {
        if ($this->is_fields_empty($this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $away_goals = $this->match['away_score'];
        $value = strtolower($bet['bet_value']); // örn: "over 1.5"

        if (preg_match('/(over|under)\s+([\d.]+)/', $value, $matches)) {
            $type = $matches[1]; // 'over' or 'under'
            $threshold = floatval($matches[2]);

            if ($type === 'over' && $away_goals > $threshold) return 'won';
            if ($type === 'under' && $away_goals < $threshold) return 'won';

            return 'lost';
        }

        return 'method_not_exist';
    }

    // Handicap Result - First Half
    public function p018($bet) {
        if ($this->is_fields_empty(
            $this->match['halftime_home_score'],
            $this->match['halftime_away_score'],
            $bet['bet_value']
        )) return 'fields_empty';

        $home = $this->match['halftime_home_score'];
        $away = $this->match['halftime_away_score'];
        $value = strtolower($bet['bet_value']); // örn: "home -1"

        if (preg_match('/^(home|away|draw)\s*([+-]?\d+)$/', $value, $matches)) {
            $type = $matches[1]; // home, away, draw
            $handicap = intval($matches[2]);

            $adjusted_home = $home;
            $adjusted_away = $away;

            if ($type === 'home' || $type === 'draw') {
                $adjusted_home += $handicap;
            } else if ($type === 'away') {
                $adjusted_away += $handicap;
            }

            if ($adjusted_home > $adjusted_away && $type === 'home') return 'won';
            if ($adjusted_away > $adjusted_home && $type === 'away') return 'won';
            if ($adjusted_home === $adjusted_away && $type === 'draw') return 'won';

            return 'lost';
        }

        return 'method_not_exist';
    }

    // Double Chance - First Half
    public function p020($bet) {
        if ($this->is_fields_empty($this->match['halftime_home_score'], $this->match['halftime_away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = $this->match['halftime_home_score'];
        $away = $this->match['halftime_away_score'];
        $value = strtolower($bet['bet_value']); // örn: "home/draw"

        $result = $home > $away ? 'home' : ($home < $away ? 'away' : 'draw');

        if (strpos($value, $result) !== false) return 'won';

        return 'lost';
    }

    // Odd/Even
    public function p021($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $total_goals = $this->match['home_score'] + $this->match['away_score'];
        $value = strtolower($bet['bet_value']); // "odd" or "even"

        if ($value === 'odd' && $total_goals % 2 === 1) return 'won';
        if ($value === 'even' && $total_goals % 2 === 0) return 'won';

        return 'lost';
    }

    // Odd/Even - First Half
    public function p022($bet) {
        if ($this->is_fields_empty($this->match['halftime_home_score'], $this->match['halftime_away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $total_goals = $this->match['halftime_home_score'] + $this->match['halftime_away_score'];
        $value = strtolower($bet['bet_value']); // "odd" or "even"

        if ($value === 'odd' && $total_goals % 2 === 1) return 'won';
        if ($value === 'even' && $total_goals % 2 === 0) return 'won';

        return 'lost';
    }

    // Home Odd/Even
    public function p023($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = $this->match['home_score'];
        $value = strtolower($bet['bet_value']); // "odd" or "even"

        if ($value === 'odd' && $home % 2 === 1) return 'won';
        if ($value === 'even' && $home % 2 === 0) return 'won';

        return 'lost';
    }

    // Results / Both Teams Score
    public function p024($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = $this->match['home_score'];
        $away = $this->match['away_score'];
        $value = strtolower(trim($bet['bet_value'])); // örn: "home/yes"

        if (!preg_match('/^(home|draw|away)\/(yes|no)$/', $value, $matches)) {
            return 'method_not_exist';
        }

        $result_part = $matches[1]; // home, draw, away
        $bts_part = $matches[2];    // yes, no

        // Maç sonucu belirle
        $result = $home > $away ? 'home' : ($home < $away ? 'away' : 'draw');

        // Both Teams Score sonucu belirle
        $bts = ($home > 0 && $away > 0) ? 'yes' : 'no';

        if ($result === $result_part && $bts === $bts_part) {
            return 'won';
        }

        return 'lost';
    }

    // Result / Total Goals
    public function p025($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = $this->match['home_score'];
        $away = $this->match['away_score'];
        $total_goals = $home + $away;
        $value = strtolower(trim($bet['bet_value'])); // örn: "home/over 2.5"

        if (!preg_match('/^(home|draw|away)\/(over|under)\s+([\d.]+)$/', $value, $matches)) {
            return 'method_not_exist';
        }

        $result_part = $matches[1];      // home, draw, away
        $ou_type = $matches[2];          // over, under
        $threshold = floatval($matches[3]); // örn: 2.5

        // Maç sonucu belirle
        $result = $home > $away ? 'home' : ($home < $away ? 'away' : 'draw');

        // Her iki koşulu kontrol et
        if (
            $result === $result_part &&
            (
                ($ou_type === 'over' && $total_goals > $threshold) ||
                ($ou_type === 'under' && $total_goals < $threshold)
            )
        ) {
            return 'won';
        }

        return 'lost';
    }

    // Goals Over/Under - Second Half
    public function p026($bet) {
        if ($this->is_fields_empty(
            $this->match['home_score'], $this->match['away_score'],
            $this->match['halftime_home_score'], $this->match['halftime_away_score'],
            $bet['bet_value']
        )) return 'fields_empty';

        $home_2h = $this->match['home_score'] - $this->match['halftime_home_score'];
        $away_2h = $this->match['away_score'] - $this->match['halftime_away_score'];
        $total_2h = $home_2h + $away_2h;

        $value = strtolower($bet['bet_value']);

        if (preg_match('/(over|under)\s+([\d.]+)/', $value, $matches)) {
            $type = $matches[1]; // 'over' or 'under'
            $threshold = floatval($matches[2]);

            if ($type === 'over' && $total_2h > $threshold) return 'won';
            if ($type === 'under' && $total_2h < $threshold) return 'won';

            return 'lost';
        }

        return 'method_not_exist';
    }

    // Clean Sheet - Home
    public function p027($bet) {
        if ($this->is_fields_empty($this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $away_goals = $this->match['away_score'];
        $value = strtolower($bet['bet_value']); // "yes" veya "no"

        if ($value === 'yes' && $away_goals == 0) return 'won';
        if ($value === 'no' && $away_goals > 0) return 'won';

        return 'lost';
    }

    // Clean Sheet - Away
    public function p028($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home_goals = $this->match['home_score'];
        $value = strtolower($bet['bet_value']); // "yes" veya "no"

        if ($value === 'yes' && $home_goals == 0) return 'won';
        if ($value === 'no' && $home_goals > 0) return 'won';

        return 'lost';
    }

    // Correct Score - First Half
    public function p031($bet) {
        if ($this->is_fields_empty($this->match['halftime_home_score'], $this->match['halftime_away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = $this->match['halftime_home_score'];
        $away = $this->match['halftime_away_score'];
        $value = trim($bet['bet_value']); // örn: "1:0"

        if (!preg_match('/^\d+:\d+$/', $value)) return 'method_not_exist';

        list($target_home, $target_away) = explode(':', $value);

        if ((int)$home === (int)$target_home && (int)$away === (int)$target_away) {
            return 'won';
        }

        return 'lost';
    }

    // Win Both Halves
    public function p032($bet) {
        if ($this->is_fields_empty(
            $this->match['halftime_home_score'], $this->match['halftime_away_score'],
            $this->match['home_score'], $this->match['away_score'],
            $bet['bet_value']
        )) return 'fields_empty';

        $ht_home = $this->match['halftime_home_score'];
        $ht_away = $this->match['halftime_away_score'];
        $ft_home = $this->match['home_score'];
        $ft_away = $this->match['away_score'];

        $sh_home = $ft_home - $ht_home;
        $sh_away = $ft_away - $ht_away;

        $value = strtolower($bet['bet_value']); // "home" or "away"

        if ($value === 'home' && $ht_home > $ht_away && $sh_home > $sh_away) return 'won';
        if ($value === 'away' && $ht_away > $ht_home && $sh_away > $sh_home) return 'won';

        return 'lost';
    }

    // Both Teams Score - First Half
    public function p034($bet) {
        if ($this->is_fields_empty($this->match['halftime_home_score'], $this->match['halftime_away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = $this->match['halftime_home_score'];
        $away = $this->match['halftime_away_score'];
        $value = strtolower($bet['bet_value']); // "yes" or "no"

        if ($value === 'yes' && $home > 0 && $away > 0) return 'won';
        if ($value === 'no' && ($home == 0 || $away == 0)) return 'won';

        return 'lost';
    }

    // Both Teams To Score - Second Half
    public function p035($bet) {
        if ($this->is_fields_empty(
            $this->match['home_score'], $this->match['away_score'],
            $this->match['halftime_home_score'], $this->match['halftime_away_score'],
            $bet['bet_value']
        )) return 'fields_empty';

        $home_2h = $this->match['home_score'] - $this->match['halftime_home_score'];
        $away_2h = $this->match['away_score'] - $this->match['halftime_away_score'];
        $value = strtolower($bet['bet_value']); // "yes" or "no"

        if ($value === 'yes' && $home_2h > 0 && $away_2h > 0) return 'won';
        if ($value === 'no' && ($home_2h == 0 || $away_2h == 0)) return 'won';

        return 'lost';
    }

    // Win To Nil
    public function p036($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = $this->match['home_score'];
        $away = $this->match['away_score'];
        $value = strtolower($bet['bet_value']); // "home" or "away"

        if ($value === 'home' && $home > $away && $away == 0) return 'won';
        if ($value === 'away' && $away > $home && $home == 0) return 'won';

        return 'lost';
    }

    // Exact Goals Number
    public function p038($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $total_goals = $this->match['home_score'] + $this->match['away_score'];
        $value = strtolower(trim((string)$bet['bet_value'])); // sayı veya string olabilir

        // "more 7" gibi ifadeler için: 7 ve üzeri
        if (preg_match('/^more\s+(\d+)$/', $value, $matches)) {
            $threshold = (int)$matches[1];
            return $total_goals >= $threshold ? 'won' : 'lost';
        }

        // Düz sayı karşılaştırması
        if (is_numeric($value)) {
            return $total_goals == (int)$value ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }

    // To Win Either Half
    public function p039($bet) {
        if ($this->is_fields_empty(
            $this->match['halftime_home_score'], $this->match['halftime_away_score'],
            $this->match['home_score'], $this->match['away_score'],
            $bet['bet_value']
        )) return 'fields_empty';

        $ht_home = $this->match['halftime_home_score'];
        $ht_away = $this->match['halftime_away_score'];
        $ft_home = $this->match['home_score'];
        $ft_away = $this->match['away_score'];

        $sh_home = $ft_home - $ht_home;
        $sh_away = $ft_away - $ht_away;

        $value = strtolower($bet['bet_value']); // "home" or "away"

        if ($value === 'home' && ($ht_home > $ht_away || $sh_home > $sh_away)) return 'won';
        if ($value === 'away' && ($ht_away > $ht_home || $sh_away > $sh_home)) return 'won';

        return 'lost';
    }

    // Home Team Exact Goals Number
    public function p040($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home_goals = $this->match['home_score'];
        $value = strtolower(trim((string)$bet['bet_value'])); // örn: "2", "more 3"

        // "more 3" gibi ifadeler
        if (preg_match('/^more\s+(\d+)$/', $value, $matches)) {
            $threshold = (int)$matches[1];
            return $home_goals >= $threshold ? 'won' : 'lost';
        }

        // düz sayı
        if (is_numeric($value)) {
            return $home_goals == (int)$value ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }

    // Away Team Exact Goals Number
    public function p041($bet) {
        if ($this->is_fields_empty($this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $away_goals = $this->match['away_score'];
        $value = strtolower(trim((string)$bet['bet_value'])); // örn: "2", "more 3"

        if (preg_match('/^more\s+(\d+)$/', $value, $matches)) {
            $threshold = (int)$matches[1];
            return $away_goals >= $threshold ? 'won' : 'lost';
        }

        if (is_numeric($value)) {
            return $away_goals == (int)$value ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }

    // Second Half Exact Goals Number
    public function p042($bet) {
        if ($this->is_fields_empty(
            $this->match['home_score'], $this->match['away_score'],
            $this->match['halftime_home_score'], $this->match['halftime_away_score'],
            $bet['bet_value']
        )) return 'fields_empty';

        $home_2h = $this->match['home_score'] - $this->match['halftime_home_score'];
        $away_2h = $this->match['away_score'] - $this->match['halftime_away_score'];
        $total_2h_goals = $home_2h + $away_2h;

        $value = strtolower(trim((string)$bet['bet_value'])); // örn: "2", "more 5"

        if (preg_match('/^more\s+(\d+)$/', $value, $matches)) {
            $threshold = (int)$matches[1];
            return $total_2h_goals >= $threshold ? 'won' : 'lost';
        }

        if (is_numeric($value)) {
            return $total_2h_goals == (int)$value ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }

    // Corners Over/Under
    public function p045($bet) {
        if ($this->is_fields_empty($this->match['stats'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $corner_data = $this->match['stats']['Corner Kicks'] ?? null;

        if (!is_array($corner_data) || count($corner_data) < 2) {
            return 'fields_empty';
        }

        $total_corners = $corner_data[0] + $corner_data[1];
        $value = strtolower(trim($bet['bet_value'])); // örn: "over 9.5"

        if (preg_match('/^(over|under)\s+([\d.]+)$/', $value, $matches)) {
            $type = $matches[1];
            $threshold = floatval($matches[2]);

            if ($type === 'over' && $total_corners > $threshold) return 'won';
            if ($type === 'under' && $total_corners < $threshold) return 'won';

            return 'lost';
        }

        return 'method_not_exist';
    }

    // Exact Goals Number - First Half
    public function p046($bet) {
        if ($this->is_fields_empty(
            $this->match['halftime_home_score'],
            $this->match['halftime_away_score'],
            $bet['bet_value']
        )) return 'fields_empty';

        $total_ht_goals = $this->match['halftime_home_score'] + $this->match['halftime_away_score'];
        $value = strtolower(trim((string)$bet['bet_value'])); // örn: "2", "more 5"

        if (preg_match('/^more\s+(\d+)$/', $value, $matches)) {
            $threshold = (int)$matches[1];
            return $total_ht_goals >= $threshold ? 'won' : 'lost';
        }

        if (is_numeric($value)) {
            return $total_ht_goals == (int)$value ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }

    // Winning Margin
    public function p047($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = (int) $this->match['home_score'];
        $away = (int) $this->match['away_score'];
        $value = strtolower(trim($bet['bet_value'])); // örn: "1 by 2", "2 by 5+", "score draw"

        $diff = abs($home - $away);

        // Draw cases
        if ($home === $away) {
            if ($home === 0 && $value === 'draw') return 'won';
            if ($home > 0 && $value === 'score draw') return 'won';
            return 'lost';
        }

        $winner = $home > $away ? '1' : '2';

        // Match pattern like "1 by 2" or "2 by 4+"
        if (preg_match('/^([12])\s+by\s+(\d+\+?)$/', $value, $matches)) {
            $side = $matches[1];
            $margin_raw = $matches[2];

            if ($side !== $winner) return 'lost';

            if (str_ends_with($margin_raw, '+')) {
                $threshold = (int) rtrim($margin_raw, '+');
                return $diff >= $threshold ? 'won' : 'lost';
            } else {
                return $diff == (int)$margin_raw ? 'won' : 'lost';
            }
        }

        return 'method_not_exist';
    }

    // To Score In Both Halves By Teams
    public function p048($bet) {
        if ($this->is_fields_empty(
            $this->match['home_score'], $this->match['away_score'],
            $this->match['halftime_home_score'], $this->match['halftime_away_score'],
            $bet['bet_value']
        )) return 'fields_empty';

        $value = strtolower(trim($bet['bet_value'])); // "home" or "away"

        if ($value === 'home') {
            $first_half = $this->match['halftime_home_score'];
            $second_half = $this->match['home_score'] - $first_half;
        } elseif ($value === 'away') {
            $first_half = $this->match['halftime_away_score'];
            $second_half = $this->match['away_score'] - $first_half;
        } else {
            return 'method_not_exist';
        }

        return ($first_half > 0 && $second_half > 0) ? 'won' : 'lost';
    }

    // Total Goals / Both Teams To Score
    public function p049($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = $this->match['home_score'];
        $away = $this->match['away_score'];
        $total_goals = $home + $away;

        $value = strtolower(trim($bet['bet_value'])); // örn: "o/yes 2.5"

        // örnek eşleşme: o/yes 2.5
        if (preg_match('/^(o|u)\/(yes|no)\s+([\d.]+)$/', $value, $matches)) {
            $over_under = $matches[1];     // o | u
            $bts = $matches[2];            // yes | no
            $threshold = floatval($matches[3]);

            $goal_check = ($over_under === 'o') ? ($total_goals > $threshold) : ($total_goals < $threshold);
            $bts_check = ($home > 0 && $away > 0) ? 'yes' : 'no';

            return ($goal_check && $bts === $bts_check) ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }

    // First 10 min Winner
    public function p054($bet) {
        if ($this->is_fields_empty(
            $this->match['events'],
            $this->match['home_team_id'],
            $this->match['away_team_id'],
            $bet['bet_value']
        )) return 'fields_empty';

        $value = strtolower(trim($bet['bet_value'])); // "home", "draw", "away"

        $home_id = $this->match['home_team_id'];
        $away_id = $this->match['away_team_id'];

        $home_goals = 0;
        $away_goals = 0;

        foreach ($this->match['events'] as $event) {
            if (
                isset($event['type'], $event['minute'], $event['team_id']) &&
                $event['type'] === 'goal' &&
                $event['minute'] < 10 // ❗ NOT INCLUDED if minute == 10
            ) {
                if ($event['team_id'] == $home_id) {
                    $home_goals++;
                } elseif ($event['team_id'] == $away_id) {
                    $away_goals++;
                }
            }
        }

        $result = $home_goals > $away_goals ? 'home' :
                ($away_goals > $home_goals ? 'away' : 'draw');

        return $value === $result ? 'won' : 'lost';
    }

    // Corners 1X2
    public function p055($bet) {
        if ($this->is_fields_empty($this->match['stats'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value'])); // "home", "draw", "away"

        $corner_data = $this->match['stats']['Corner Kicks'] ?? null;

        if (!is_array($corner_data) || count($corner_data) < 2) {
            return 'fields_empty';
        }

        $home_corners = $corner_data[0];
        $away_corners = $corner_data[1];

        $result = $home_corners > $away_corners ? 'home' :
                ($away_corners > $home_corners ? 'away' : 'draw');

        return $value === $result ? 'won' : 'lost';
    }

    // Home Corners Over/Under
    public function p057($bet) {
        if ($this->is_fields_empty($this->match['stats'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home_corners = $this->match['stats']['Corner Kicks'][0] ?? null;

        if (!is_numeric($home_corners)) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value'])); // örn: "over 4.5"

        if (preg_match('/^(over|under)\s+([\d.]+)$/', $value, $matches)) {
            $type = $matches[1];
            $threshold = floatval($matches[2]);

            if ($type === 'over' && $home_corners > $threshold) return 'won';
            if ($type === 'under' && $home_corners < $threshold) return 'won';

            return 'lost';
        }

        return 'method_not_exist';
    }

    // Away Corners Over/Under
    public function p058($bet) {
        if ($this->is_fields_empty($this->match['stats'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $away_corners = $this->match['stats']['Corner Kicks'][1] ?? null;

        if (!is_numeric($away_corners)) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value'])); // örn: "over 5.5"

        if (preg_match('/^(over|under)\s+([\d.]+)$/', $value, $matches)) {
            $type = $matches[1];
            $threshold = floatval($matches[2]);

            if ($type === 'over' && $away_corners > $threshold) return 'won';
            if ($type === 'under' && $away_corners < $threshold) return 'won';

            return 'lost';
        }

        return 'method_not_exist';
    }

    // Own Goal
    public function p059($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value'])); // "yes" or "no"

        $has_own_goal = false;

        foreach ($this->match['events'] as $event) {
            if (
                isset($event['type'], $event['detail']) &&
                $event['type'] === 'goal' &&
                strtolower($event['detail']) === 'own_goal'
            ) {
                $has_own_goal = true;
                break;
            }
        }

        if ($value === 'yes' && $has_own_goal) return 'won';
        if ($value === 'no' && !$has_own_goal) return 'won';

        return 'lost';
    }

    // Away Odd/Even
    public function p060($bet) {
        if ($this->is_fields_empty($this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $away = $this->match['away_score'];
        $value = strtolower($bet['bet_value']); // "odd" or "even"

        if ($value === 'odd' && $away % 2 === 1) return 'won';
        if ($value === 'even' && $away % 2 === 0) return 'won';

        return 'lost';
    }

    // Odd/Even - Second Half
    public function p063($bet) {
        if ($this->is_fields_empty(
            $this->match['home_score'], $this->match['away_score'],
            $this->match['halftime_home_score'], $this->match['halftime_away_score'],
            $bet['bet_value']
        )) return 'fields_empty';

        $home_2h = $this->match['home_score'] - $this->match['halftime_home_score'];
        $away_2h = $this->match['away_score'] - $this->match['halftime_away_score'];
        $total_2h_goals = $home_2h + $away_2h;

        $value = strtolower(trim($bet['bet_value'])); // "odd" or "even"

        if ($value === 'odd' && $total_2h_goals % 2 === 1) return 'won';
        if ($value === 'even' && $total_2h_goals % 2 === 0) return 'won';

        return 'lost';
    }

    // Total Corners (3 way)
    public function p085($bet) {
        if ($this->is_fields_empty($this->match['stats'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $corner_data = $this->match['stats']['Corner Kicks'] ?? null;

        if (!is_array($corner_data) || count($corner_data) < 2) {
            return 'fields_empty';
        }

        $total_corners = $corner_data[0] + $corner_data[1];
        $value = strtolower(trim($bet['bet_value'])); // örn: "exactly 4", "over 5"

        if (preg_match('/^(exactly|over|under)\s+(\d+)$/', $value, $matches)) {
            $type = $matches[1];         // exactly | over | under
            $target = (int) $matches[2]; // örn: 4, 5

            if ($type === 'exactly' && $total_corners === $target) return 'won';
            if ($type === 'over' && $total_corners > $target) return 'won';
            if ($type === 'under' && $total_corners < $target) return 'won';

            return 'lost';
        }

        return 'method_not_exist';
    }

    // Total ShotOnGoal
    public function p087($bet) {
        if ($this->is_fields_empty($this->match['stats'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $shots = $this->match['stats']['Shots on Goal'] ?? null;

        if (!is_array($shots) || count($shots) < 2) {
            return 'fields_empty';
        }

        $total = $shots[0] + $shots[1];
        $value = strtolower(trim($bet['bet_value'])); // örn: "over 8.5"

        if (preg_match('/^(over|under)\s+([\d.]+)$/', $value, $matches)) {
            $type = $matches[1];
            $threshold = floatval($matches[2]);

            if ($type === 'over' && $total > $threshold) return 'won';
            if ($type === 'under' && $total < $threshold) return 'won';

            return 'lost';
        }

        return 'method_not_exist';
    }

    // Anytime Goal Scorer
    public function p092($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $target_name = strtolower(trim($bet['bet_value'])); // Örn: "bruno fernandes"

        foreach ($this->match['events'] as $event) {
            if (
                isset($event['type'], $event['detail'], $event['player_name']) &&
                $event['type'] === 'goal' &&
                strtolower($event['detail']) !== 'own_goal'
            ) {
                $scorer = strtolower(trim($event['player_name']));
                if ($scorer === $target_name) {
                    return 'won';
                }
            }
        }

        return 'lost';
    }

    // First Goal Scorer
    public function p093($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $target_name = strtolower(trim($bet['bet_value'])); // örn: "sekou kone"

        foreach ($this->match['events'] as $event) {
            if (
                isset($event['type'], $event['detail'], $event['player_name']) &&
                $event['type'] === 'goal' &&
                strtolower($event['detail']) !== 'own_goal'
            ) {
                $scorer = strtolower(trim($event['player_name']));
                return $scorer === $target_name ? 'won' : 'lost'; // sadece ilk gol kontrol edilir
            }
        }

        return 'lost'; // hiç gol yoksa
    }

    // Last Goal Scorer
    public function p094($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $target_name = strtolower(trim($bet['bet_value'])); // örn: "aimar dunabeitia"
        $last_scorer = null;

        foreach ($this->match['events'] as $event) {
            if (
                isset($event['type'], $event['detail'], $event['player_name']) &&
                $event['type'] === 'goal' &&
                strtolower($event['detail']) !== 'own_goal'
            ) {
                $last_scorer = strtolower(trim($event['player_name']));
            }
        }

        if (!$last_scorer) return 'lost'; // gol atılmadıysa

        return $target_name === $last_scorer ? 'won' : 'lost';
    }

    // First Goal Method
    public function p097($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value'])); // örn: "penalty", "shot", "draw"

        // Eğer maçta hiç gol yoksa ve kullanıcı "Draw" demişse
        if (
            ($this->match['home_score'] ?? 0) == 0 &&
            ($this->match['away_score'] ?? 0) == 0
        ) {
            return $value === 'draw' ? 'won' : 'lost';
        }

        // İlk gol olayını bul
        foreach ($this->match['events'] as $event) {
            if (($event['type'] ?? '') === 'goal') {
                $detail = strtolower($event['detail'] ?? '');

                // detail -> sistemdeki bet_value eşleşmesi
                $map = [
                    'normal_goal' => 'shot',
                    'penalty'     => 'penalty',
                    'own_goal'    => 'owngoal',
                    'free_kick'   => 'freekick',
                    'header'      => 'header',
                ];

                $first_method = $map[$detail] ?? null;

                if (!$first_method) return 'method_not_exist';

                return $value === $first_method ? 'won' : 'lost';
            }
        }

        return 'lost'; // gol bekleniyordu ama hiçbiri bulunamadıysa
    }

    // To Score A Penalty
    public function p099($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value'])); // "home" veya "away"

        $team_id = null;
        if ($value === 'home') $team_id = $this->match['home_team_id'] ?? null;
        if ($value === 'away') $team_id = $this->match['away_team_id'] ?? null;

        if (!$team_id) return 'fields_empty';

        foreach ($this->match['events'] as $event) {
            if (
                isset($event['type'], $event['detail'], $event['team_id']) &&
                $event['type'] === 'goal' &&
                strtolower($event['detail']) === 'penalty' &&
                $event['team_id'] == $team_id
            ) {
                return 'won';
            }
        }

        return 'lost';
    }

    // To Miss A Penalty
    public function p100($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value'])); // "home" veya "away"

        $team_id = null;
        if ($value === 'home') $team_id = $this->match['home_team_id'] ?? null;
        if ($value === 'away') $team_id = $this->match['away_team_id'] ?? null;

        if (!$team_id) return 'fields_empty';

        foreach ($this->match['events'] as $event) {
            if (
                isset($event['type'], $event['detail'], $event['team_id']) &&
                strtolower($event['detail']) === 'missed_penalty' &&
                $event['team_id'] == $team_id
            ) {
                return 'won';
            }
        }

        return 'lost';
    }

    // ShotOnTarget 1x2
    public function p176($bet) {
        if ($this->is_fields_empty($this->match['stats'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $shots = $this->match['stats']['Shots on Goal'] ?? null;
        if (!is_array($shots) || count($shots) < 2) return 'fields_empty';

        $home = (int) $shots[0];
        $away = (int) $shots[1];
        $value = strtolower(trim($bet['bet_value'])); // "home", "away", "draw"

        $result = 'draw';
        if ($home > $away) $result = 'home';
        elseif ($away > $home) $result = 'away';

        return $value === $result ? 'won' : 'lost';
    }

    // Home Highest Scoring Half
    public function p192($bet) {
        if ($this->is_fields_empty(
            $this->match['home_score'],
            $this->match['halftime_home_score'],
            $bet['bet_value']
        )) return 'fields_empty';

        $value = strtolower(trim($bet['bet_value'])); // "draw", "1st half", "2nd half"

        $h1 = (int) $this->match['halftime_home_score'];
        $h2 = (int) $this->match['home_score'] - $h1;

        $result = 'draw';
        if ($h1 > $h2) $result = '1st half';
        elseif ($h2 > $h1) $result = '2nd half';

        return $value === strtolower($result) ? 'won' : 'lost';
    }

    // Away Highest Scoring Half
    public function p193($bet) {
        if ($this->is_fields_empty(
            $this->match['away_score'],
            $this->match['halftime_away_score'],
            $bet['bet_value']
        )) return 'fields_empty';

        $value = strtolower(trim($bet['bet_value'])); // "draw", "1st half", "2nd half"

        $a1 = (int) $this->match['halftime_away_score'];
        $a2 = (int) $this->match['away_score'] - $a1;

        $result = 'draw';
        if ($a1 > $a2) $result = '1st half';
        elseif ($a2 > $a1) $result = '2nd half';

        return $value === strtolower($result) ? 'won' : 'lost';
    }

    // Away First Goal Scorer
    public function p219($bet) {
        if ($this->is_fields_empty($this->match['events'], $this->match['away_team_id'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value'])); // oyuncu adı

        foreach ($this->match['events'] as $event) {
            if (
                isset($event['type'], $event['detail'], $event['team_id']) &&
                $event['type'] === 'goal' &&
                strtolower($event['detail']) !== 'own_goal' &&
                $event['team_id'] == $this->match['away_team_id']
            ) {
                $scorer = strtolower(trim($event['player_name'] ?? ''));
                return $scorer === $value ? 'won' : 'lost'; // ilk away golü bulundu
            }
        }

        return 'lost'; // away gol atamamış
    }

    // Away Last Goal Scorer
    public function p226($bet) {
        if ($this->is_fields_empty($this->match['events'], $this->match['away_team_id'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value']));
        $last_away_goal_scorer = null;

        foreach ($this->match['events'] as $event) {
            if (
                isset($event['type'], $event['detail'], $event['team_id'], $event['player_name']) &&
                $event['type'] === 'goal' &&
                strtolower($event['detail']) !== 'own_goal' &&
                $event['team_id'] == $this->match['away_team_id']
            ) {
                $last_away_goal_scorer = strtolower(trim($event['player_name']));
            }
        }

        if (!$last_away_goal_scorer) {
            return 'lost'; // away takım gol atmamış
        }

        return $last_away_goal_scorer === $value ? 'won' : 'lost';
    }

    // Home Goal Method Header
    public function p228($bet) {
        if ($this->is_fields_empty($this->match['events'], $this->match['home_team_id'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value']));
        $team_id = $this->match['home_team_id'];

        foreach ($this->match['events'] as $event) {
            if (
                isset($event['type'], $event['detail'], $event['team_id'], $event['player_name']) &&
                $event['type'] === 'goal' &&
                strtolower($event['detail']) === 'header' &&
                $event['team_id'] == $team_id &&
                strtolower(trim($event['player_name'])) === $value
            ) {
                return 'won';
            }
        }

        return 'lost';
    }

    // Goal Method Outside the Box
    public function p229($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value']));

        foreach ($this->match['events'] as $event) {
            if (
                isset($event['type'], $event['detail'], $event['player_name']) &&
                $event['type'] === 'goal' &&
                strtolower($event['detail']) === 'outside_the_box' &&
                strtolower(trim($event['player_name'])) === $value
            ) {
                return 'won';
            }
        }

        return 'lost';
    }

    // Corners. European Handicap
    public function p239($bet) {
        if ($this->is_fields_empty($this->match['stats'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value'])); // örn: "home +1"

        $corners = $this->match['stats']['Corner Kicks'] ?? null;
        if (!is_array($corners) || count($corners) < 2) return 'fields_empty';

        $home = (int) $corners[0];
        $away = (int) $corners[1];

        if (preg_match('/^(home|away|draw)\s*([+-]\d+)$/', $value, $matches)) {
            $side = $matches[1];               // home | away | draw
            $handicap = (int)$matches[2];      // +1, -1, vs.

            if ($side === 'draw') {
                $adjusted_diff = ($home - $away) + $handicap;
                return $adjusted_diff === 0 ? 'won' : 'lost';
            }

            // Uygulanmış skorlar
            if ($side === 'home') $home += $handicap;
            if ($side === 'away') $away += $handicap;

            if ($side === 'home' && $home > $away) return 'won';
            if ($side === 'away' && $away > $home) return 'won';

            return 'lost';
        }

        return 'method_not_exist';
    }

    // Away Goal Method Header
    public function p245($bet) {
        if ($this->is_fields_empty($this->match['events'], $this->match['away_team_id'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value']));
        $team_id = $this->match['away_team_id'];

        foreach ($this->match['events'] as $event) {
            if (
                isset($event['type'], $event['detail'], $event['team_id'], $event['player_name']) &&
                $event['type'] === 'goal' &&
                strtolower($event['detail']) === 'header' &&
                $event['team_id'] == $team_id &&
                strtolower(trim($event['player_name'])) === $value
            ) {
                return 'won';
            }
        }

        return 'lost';
    }

    // Match Corners (Over/Under/Exactly)
    public function l020($bet) {
        if ($this->is_fields_empty($this->match['stats'], $bet['bet_value'], $bet['handicap'])) {
            return 'fields_empty';
        }

        $corners = $this->match['stats']['Corner Kicks'] ?? null;
        if (!is_array($corners) || count($corners) < 2) return 'fields_empty';

        $total = (int)$corners[0] + (int)$corners[1];
        $value = strtolower(trim($bet['bet_value']));
        $handicap = floatval($bet['handicap']);

        if ($value === 'over') {
            return $total > $handicap ? 'won' : 'lost';
        } elseif ($value === 'under') {
            return $total < $handicap ? 'won' : 'lost';
        } elseif ($value === 'exactly') {
            return $total == $handicap ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }

    // 3-Way Handicap
    public function l021($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $this->match['away_score'], $bet['bet_value'], $bet['handicap'])) {
            return 'fields_empty';
        }

        $home = (int)$this->match['home_score'];
        $away = (int)$this->match['away_score'];
        $value = strtolower(trim($bet['bet_value'])); // home / draw / away
        $handicap = floatval($bet['handicap']);

        // Handikap uygulandıktan sonra skoru yeniden değerlendir
        $adjusted_home = $home;
        $adjusted_away = $away;

        if ($value === 'home') {
            $adjusted_home += $handicap;
        } elseif ($value === 'away') {
            $adjusted_away += $handicap;
        } elseif ($value === 'draw') {
            // draw için farkı kapatmak adına evden handikap çıkarılır
            $adjusted_home += $handicap;
        } else {
            return 'method_not_exist';
        }

        if ($adjusted_home > $adjusted_away && $value === 'home') return 'won';
        if ($adjusted_home < $adjusted_away && $value === 'away') return 'won';
        if ($adjusted_home === $adjusted_away && $value === 'draw') return 'won';

        return 'lost';
    }

    // 1X2 - 30 Minutes
    public function l022($bet) {
        return $this->m_1x2($bet, 30);
    }

    // Live Match Goals
    public function l025($bet) {
        if ($this->is_fields_empty(
            $this->match['home_score'],
            $this->match['away_score'],
            $bet['bet_value'],
            $bet['handicap']
        )) return 'fields_empty';

        $total_goals = (int)$this->match['home_score'] + (int)$this->match['away_score'];
        $value = strtolower(trim($bet['bet_value'])); // "over" or "under"
        $handicap = floatval($bet['handicap']);

        if ($value === 'over') {
            return $total_goals > $handicap ? 'won' : 'lost';
        } elseif ($value === 'under') {
            return $total_goals < $handicap ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }

    // Home Team to Score in Both Halves
    public function l028($bet) {
        if ($this->is_fields_empty($this->match['halftime_home_score'], $this->match['home_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $first_half = (int)$this->match['halftime_home_score'];
        $total = (int)$this->match['home_score'];
        $second_half = $total - $first_half;

        $value = strtolower(trim($bet['bet_value'])); // yes / no
        $scored_both_halves = ($first_half > 0 && $second_half > 0);

        if ($value === 'yes') return $scored_both_halves ? 'won' : 'lost';
        if ($value === 'no') return !$scored_both_halves ? 'won' : 'lost';

        return 'method_not_exist';
    }

    // Both Teams To Score (1st Half)
    public function l030($bet) {
        if ($this->is_fields_empty($this->match['halftime_home_score'], $this->match['halftime_away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = (int)$this->match['halftime_home_score'];
        $away = (int)$this->match['halftime_away_score'];
        $value = strtolower(trim($bet['bet_value'])); // yes / no

        $both_scored = $home > 0 && $away > 0;

        if ($value === 'yes') return $both_scored ? 'won' : 'lost';
        if ($value === 'no') return !$both_scored ? 'won' : 'lost';

        return 'method_not_exist';
    }

    // 1X2 - 40 Minutes
    public function l034($bet) {
        return $this->m_1x2($bet, 40);
    }

    // To Win 2nd Half
    public function l035($bet) {
        if ($this->is_fields_empty(
            $this->match['home_score'],
            $this->match['away_score'],
            $this->match['halftime_home_score'],
            $this->match['halftime_away_score'],
            $bet['bet_value']
        )) {
            return 'fields_empty';
        }

        $home_2h = (int)$this->match['home_score'] - (int)$this->match['halftime_home_score'];
        $away_2h = (int)$this->match['away_score'] - (int)$this->match['halftime_away_score'];
        $value = strtolower(trim($bet['bet_value'])); // home / away / draw

        if ($value === 'home') return $home_2h > $away_2h ? 'won' : 'lost';
        if ($value === 'away') return $away_2h > $home_2h ? 'won' : 'lost';
        if ($value === 'draw') return $home_2h === $away_2h ? 'won' : 'lost';

        return 'method_not_exist';
    }

    // Total Corners (Live)
    public function l037($bet) {
        if ($this->is_fields_empty($this->match['stats'], $bet['bet_value'], $bet['handicap'])) {
            return 'fields_empty';
        }

        $corners = $this->match['stats']['Corner Kicks'] ?? null;
        if (!is_array($corners) || count($corners) < 2) return 'fields_empty';

        $home = (int)$corners[0];
        $away = (int)$corners[1];
        $total = $home + $away;

        $value = strtolower(trim($bet['bet_value'])); // over / under
        $handicap = floatval($bet['handicap']);

        if ($value === 'over') {
            return $total > $handicap ? 'won' : 'lost';
        } elseif ($value === 'under') {
            return $total < $handicap ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }

    // 1X2 - 50 Minutes
    public function l041($bet) {
        return $this->m_1x2($bet, 50);
    }

    // Both Teams To Score (2nd Half)
    public function l043($bet) {
        if ($this->is_fields_empty(
            $this->match['halftime_home_score'],
            $this->match['halftime_away_score'],
            $this->match['home_score'],
            $this->match['away_score'],
            $bet['bet_value']
        )) {
            return 'fields_empty';
        }

        $value = strtolower($bet['bet_value']);

        $home_goals_2h = $this->match['home_score'] - $this->match['halftime_home_score'];
        $away_goals_2h = $this->match['away_score'] - $this->match['halftime_away_score'];

        $home_scored = $home_goals_2h > 0;
        $away_scored = $away_goals_2h > 0;

        if ($value === 'yes') {
            return ($home_scored && $away_scored) ? 'won' : 'lost';
        }

        if ($value === 'no') {
            return (!$home_scored || !$away_scored) ? 'won' : 'lost';
        }

        return 'lost';
    }

    // Over/Under (1st Half)
    public function l049($bet) {
        if ($this->is_fields_empty($this->match['halftime_home_score'], $this->match['halftime_away_score'], $bet['bet_value'], $bet['handicap'])) {
            return 'fields_empty';
        }

        $home = (int)$this->match['halftime_home_score'];
        $away = (int)$this->match['halftime_away_score'];
        $total = $home + $away;

        $value = strtolower(trim($bet['bet_value'])); // over / under
        $handicap = floatval($bet['handicap']);

        if ($value === 'over') {
            return $total > $handicap ? 'won' : 'lost';
        } elseif ($value === 'under') {
            return $total < $handicap ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }

    // 1X2 - 60 Minutes
    public function l050($bet) {
        return $this->m_1x2($bet, 60);
    }

    // 1X2 - 80 Minutes
    public function l052($bet) {
        return $this->m_1x2($bet, 80);
    }

    // To Score 2 or More
    public function l053($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $player = strtolower(trim($bet['bet_value']));
        $goal_count = 0;

        foreach ($this->match['events'] as $event) {
            if ($event['type'] === 'goal') {
                $detail = strtolower($event['detail'] ?? '');
                if ($detail === 'own_goal') continue;

                $scorer = strtolower($event['player_name'] ?? '');
                if ($scorer === $player) {
                    $goal_count++;
                    if ($goal_count >= 2) return 'won';
                }
            }
        }

        return 'lost';
    }

    // Correct Score (1st Half)
    public function l055($bet) {
        if ($this->is_fields_empty($this->match['halftime_home_score'], $this->match['halftime_away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = (int)$this->match['halftime_home_score'];
        $away = (int)$this->match['halftime_away_score'];

        $expected = explode('-', str_replace(' ', '', trim($bet['bet_value']))); // örn: "2-1" → [2, 1]
        if (count($expected) !== 2) return 'method_not_exist';

        $exp_home = (int)$expected[0];
        $exp_away = (int)$expected[1];

        return ($home === $exp_home && $away === $exp_away) ? 'won' : 'lost';
    }

    // 1X2 - 70 Minutes
    public function l056($bet) {
        return $this->m_1x2($bet, 70);
    }

    // Away Team Clean Sheet
    public function l057($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home_goals = (int)$this->match['home_score'];
        $value = strtolower(trim($bet['bet_value'])); // yes / no

        if ($value === 'yes') {
            return $home_goals === 0 ? 'won' : 'lost';
        } elseif ($value === 'no') {
            return $home_goals > 0 ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }

    // Home Team Goals Over/Under
    public function l058($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $bet['bet_value'], $bet['handicap'])) {
            return 'fields_empty';
        }

        $goals = (int)$this->match['home_score'];
        $value = strtolower(trim($bet['bet_value'])); // over / under
        $handicap = floatval($bet['handicap']);

        if ($value === 'over') {
            return $goals > $handicap ? 'won' : 'lost';
        } elseif ($value === 'under') {
            return $goals < $handicap ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }

    // To Score 3 or More
    public function l060($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $player = strtolower(trim($bet['bet_value']));
        $goal_count = 0;

        foreach ($this->match['events'] as $event) {
            if ($event['type'] === 'goal') {
                $detail = strtolower($event['detail'] ?? '');
                if ($detail === 'own_goal') continue;

                $scorer = strtolower($event['player_name'] ?? '');
                if ($scorer === $player) {
                    $goal_count++;
                    if ($goal_count >= 3) return 'won';
                }
            }
        }

        return 'lost';
    }

    //Last Team to Score (3 way)
    public function l062($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'], $this->match['home_team_id'], $this->match['away_team_id'])) {
            return 'fields_empty';
        }
    
        $value = strtolower(trim($bet['bet_value']));
        $events = array_reverse($this->match['events']);
        $home_id = $this->match['home_team_id'];
        $away_id = $this->match['away_team_id'];
    
        $last_goal_team = null;
    
        foreach ($events as $event) {
            if ($event['type'] === 'goal') {
                $last_goal_team = $event['team_id'];
                break; // sadece en son golü bul ve çık
            }
        }
    
        if (is_null($last_goal_team)) {
            return $value === 'no goal' ? 'won' : 'lost';
        }
    
        if ($value === '1' && $last_goal_team == $home_id) return 'won';
        if ($value === '2' && $last_goal_team == $away_id) return 'won';
        return 'lost';
    }
    
    // Anytime Goal Scorer
    public function l063($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $player = strtolower(trim($bet['bet_value']));
        foreach ($this->match['events'] as $event) {
            if ($event['type'] === 'goal') {
                $goal_type = strtolower($event['detail'] ?? '');
                $scorer = strtolower($event['player_name'] ?? '');

                // Own goal sayılmaz
                if ($goal_type === 'own_goal') continue;

                if ($scorer === $player) {
                    return 'won';
                }
            }
        }

        return 'lost';
    }

    // Half Time / Full Time
    public function l064($bet) {
        if ($this->is_fields_empty(
            $this->match['halftime_home_score'],
            $this->match['halftime_away_score'],
            $this->match['home_score'],
            $this->match['away_score'],
            $bet['bet_value']
        )) {
            return 'fields_empty';
        }

        $value = strtoupper(trim($bet['bet_value'])); // örn: 1/X

        $ht_home = (int)$this->match['halftime_home_score'];
        $ht_away = (int)$this->match['halftime_away_score'];
        $ft_home = (int)$this->match['home_score'];
        $ft_away = (int)$this->match['away_score'];

        // İlk yarı sonucu
        if ($ht_home > ht_away) $ht = '1';
        elseif ($ht_home < ht_away) $ht = '2';
        else $ht = 'X';

        // Maç sonucu
        if ($ft_home > $ft_away) $ft = '1';
        elseif ($ft_home < $ft_away) $ft = '2';
        else $ft = 'X';

        return ($value === "$ht/$ft") ? 'won' : 'lost';
    }

    // Double Chance (Live)
    public function l072($bet) {
        if ($this->is_fields_empty($this->match['home_score'], $this->match['away_score'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $home = (int)$this->match['home_score'];
        $away = (int)$this->match['away_score'];
        $value = strtolower(trim($bet['bet_value'])); // "home or draw", "away or draw", "home or away"

        if ($value === 'home or draw') {
            return ($home >= $away) ? 'won' : 'lost';
        } elseif ($value === 'away or draw') {
            return ($away >= $home) ? 'won' : 'lost';
        } elseif ($value === 'home or away') {
            return ($home !== $away) ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }

    // Corners European Handicap (3-way)
    public function l076($bet) {
        if ($this->is_fields_empty($this->match['stats'], $bet['bet_value'], $bet['handicap'])) {
            return 'fields_empty';
        }

        $corners = $this->match['stats']['Corner Kicks'] ?? null;
        if (!is_array($corners) || count($corners) < 2) return 'fields_empty';

        $home = (int)$corners[0];
        $away = (int)$corners[1];
        $value = strtolower(trim($bet['bet_value'])); // home / draw / away
        $handicap = floatval($bet['handicap']);

        $adjusted_home = $home;
        $adjusted_away = $away;

        if ($value === 'home') {
            $adjusted_home += $handicap;
        } elseif ($value === 'away') {
            $adjusted_away += $handicap;
        } elseif ($value === 'draw') {
            $adjusted_home += $handicap; // draw için handikap home’a uygulanır
        } else {
            return 'method_not_exist';
        }

        if ($adjusted_home > $adjusted_away && $value === 'home') return 'won';
        if ($adjusted_home < $adjusted_away && $value === 'away') return 'won';
        if ($adjusted_home === $adjusted_away && $value === 'draw') return 'won';

        return 'lost';
    }

    // 1X2 - 10 Minutes
    public function l077($bet) {
        return $this->m_1x2($bet, 10);
    }

    // Corners 1X2
    public function l078($bet) {
        if ($this->is_fields_empty($this->match['stats'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value']));
        $corners = $this->match['stats']['Corner Kicks'] ?? null;

        if (!is_array($corners) || count($corners) < 2) {
            return 'fields_empty';
        }

        $home = (int)$corners[0];
        $away = (int)$corners[1];

        if ($home > $away && $value === 'home') return 'won';
        if ($away > $home && $value === 'away') return 'won';
        if ($home === $away && $value === 'draw') return 'won';

        return 'lost';
    }

    // 1X2 - 20 Minutes
    public function l079($bet) {
        return $this->m_1x2($bet, 20);
    }

    // Method of 1st Goal
    public function l080($bet) {
        return $this->m_goal_method($bet, 0);
    }

    // Which team will score the 2nd goal?
    public function l085($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'], $this->match['home_team_id'], $this->match['away_team_id'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim((string)$bet['bet_value']));
        $home_id = $this->match['home_team_id'];
        $away_id = $this->match['away_team_id'];
        $goal_events = [];

        foreach ($this->match['events'] as $event) {
            if ($event['type'] === 'goal' && strtolower($event['detail'] ?? '') !== 'own_goal') {
                $goal_events[] = $event;
                if (count($goal_events) === 2) break; // sadece ilk 2 golü kontrol et
            }
        }

        if (count($goal_events) < 2 && $value === 'no goal') return 'won';
        if (count($goal_events) < 2) return 'lost';

        $second_goal = $goal_events[1];

        if ($second_goal['team_id'] == $home_id && $value === '1') return 'won';
        if ($second_goal['team_id'] == $away_id && $value === '2') return 'won';

        return 'lost';
    }

    // 2nd Goal in Interval (Live)
    public function l090($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'], $bet['handicap'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value']));  // "yes" or "no"
        $limit_minute = (int)$bet['handicap'];

        $goal_minutes = [];

        foreach ($this->match['events'] as $event) {
            if (
                isset($event['type'], $event['minute']) &&
                $event['type'] === 'goal'
            ) {
                $goal_minutes[] = (int)$event['minute'];
            }
        }

        if (count($goal_minutes) < 2) {
            return $value === 'no' ? 'won' : 'lost';
        }

        sort($goal_minutes); // dakika sırasına göre sırala
        $second_goal_minute = $goal_minutes[1];

        if ($value === 'yes') {
            return $second_goal_minute <= $limit_minute ? 'won' : 'lost';
        } elseif ($value === 'no') {
            return $second_goal_minute > $limit_minute ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }

    // Away 2nd Goal in Interval
    public function l091($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'], $bet['handicap'], $this->match['away_team_id'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value']));
        $threshold = (int)$bet['handicap']; // dakika
        $away_id = $this->match['away_team_id'];

        $goals = 0;

        foreach ($this->match['events'] as $event) {
            if ($event['type'] === 'goal' && $event['team_id'] == $away_id) {
                $minute = (int)($event['minute'] ?? 0);
                if ($minute <= $threshold) {
                    $goals++;
                    if ($goals == 2) break;
                }
            }
        }

        if ($value === 'yes') {
            return $goals >= 2 ? 'won' : 'lost';
        } elseif ($value === 'no') {
            return $goals >= 2 ? 'lost' : 'won';
        }

        return 'method_not_exist';
    }

    // Method of 2nd Goal
    public function l113($bet) {
        return $this->m_goal_method($bet, 1);
    }

    // Player to be Sent Off
    public function l118($bet) {
        if ($this->is_fields_empty($this->match['events'], $bet['bet_value'])) {
            return 'fields_empty';
        }

        $target_player = strtolower(trim($bet['bet_value']));
        $events = $this->match['events'];

        foreach ($events as $event) {
            if (
                $event['type'] === 'card' &&
                in_array($event['detail'], ['red_card', 'second_yellow']) &&
                strtolower(trim($event['player_name'])) === $target_player
            ) {
                return 'won';
            }
        }

        return 'lost';
    }

    // Method of 3rd Goal
    public function l130($bet) {
        return $this->m_goal_method($bet, 2);
    }
    
    // Method of 4th Goal
    public function l138($bet) {
        return $this->m_goal_method($bet, 3);
    }
    
    // Method of 5th Goal
    public function l145($bet) {
        return $this->m_goal_method($bet, 4);
    }
    
    // Method of 6th Goal
    public function l146($bet) {
        return $this->m_goal_method($bet, 5);
    }

    // Total Shots on Goal Over/Under
    public function l150($bet) {
        if ($this->is_fields_empty($this->match['stats'], $bet['bet_value'], $bet['handicap'])) {
            return 'fields_empty';
        }

        $value = strtolower(trim($bet['bet_value'])); // over / under
        $handicap = floatval($bet['handicap']);

        $stats = $this->match['stats']['Shots on Goal'] ?? null;
        if (!is_array($stats) || count($stats) < 2) return 'fields_empty';

        $total_shots = intval($stats[0]) + intval($stats[1]);

        if ($value === 'over') {
            return $total_shots > $handicap ? 'won' : 'lost';
        } elseif ($value === 'under') {
            return $total_shots < $handicap ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }

    // Home Total Shots Over/Under
    public function l152($bet) {
        if ($this->is_fields_empty($this->match['stats'], $bet['bet_value'], $bet['handicap'])) {
            return 'fields_empty';
        }

        $shots = $this->match['stats']['Total Shots'][0] ?? null;
        if ($shots === null) return 'fields_empty';

        $value = strtolower(trim($bet['bet_value'])); // over / under
        $handicap = floatval($bet['handicap']);

        if ($value === 'over') {
            return $shots > $handicap ? 'won' : 'lost';
        } elseif ($value === 'under') {
            return $shots < $handicap ? 'won' : 'lost';
        }

        return 'method_not_exist';
    }
    
    // Method of 7th Goal
    public function l157($bet) {
        return $this->m_goal_method($bet, 6);
    }
    
    // Method of 8th Goal
    public function l158($bet) {
        return $this->m_goal_method($bet, 7);
    }

}

?>
