<?php

class Matches {
    private $db;  

    public function __construct() {
        $this->db = new DB();
    }

    /**
     * Get match by key(match_id, fixture_id etc.)
     * @param int $key
     * @param int $col(optional)
     * @return array|null
     */
    public function get($key, $col = 'match_id') {
        return $this->db->from('matches')->where($col, $key)->first();
    }

    /**
     * Get match by key(match_id, fixture_id etc.)
     * @param int $key
     * @param int $col(optional)
     * @return array|null
     */
    public function get_full($key, $col = 'match_id') {
        $item = $this->db->from('matches')->where($col, $key)->select(['match_id', 'match_data', 'match_odds', 'stats', 'current_minute', 'current_second'])->first();
        if(!$item) return FALSE;
        $match = json_decode($item['match_data'], TRUE);
        $match['odds'] = $item['match_odds'] ?json_decode($item['match_odds'], TRUE) : [];
        $match['stats'] = $item['stats'] ? json_decode($item['stats'], TRUE) : [];
        $match['match_id'] = $item['match_id'];
        $match['current_minute'] = $item['current_minute'];
        $match['current_second'] = $item['current_second'];
        return $match;
    }

    /**
     * Get live matches
     * @return array
     */
    public function get_live_matches() {
        return $this->db->from('matches')->where('status', 'live')->all();
    }

    /**
     * Update match data
     * @param int $match_id
     * @param array $data
     * @return void
     */
    public function update_match($match_id, $data) {
        $this->db->update('matches')->where('match_id', $match_id)->set($data);
    }

    /**
     * Delete match
     * @param int $match_id
     * @return void
     */
    public function delete_match($match_id) {
        $this->db->delete('matches')->where('match_id', $match_id)->done();
    }

    /**
     * Get matches grouped by leagues.
     *
     * @param array $params Filtering options like status, league_id, limit, from_day, to_day.
     * @return array Grouped matches by league.
     */
    public function get_grouped_matches($params = []) {
        $query = $this->db->from('matches')->select(['match_id', 'match_data', 'match_odds', 'current_minute', 'current_second']);

        // Apply filters
        if (!empty($params['status'])) {
            $statuses = is_array($params['status']) ? $params['status'] : [$params['status']];
            $query->where('status', $statuses,'IN');
        }
        if (!empty($params['league_id'])) {
            $query->where('league_id', (int)$params['league_id']);
        }
        if (!empty($params['limit'])) {
            $query->limit(0, (int)$params['limit']);
        }

        // Day range filter
        $from_day = isset($params['from_day']) ? (int)$params['from_day'] : 0;
        $to_day = isset($params['to_day']) ? (int)$params['to_day'] : 0;

        // Calculate timestamps for the day range
        $start_date = strtotime("+" . $from_day . " days", strtotime(date("Y-m-d")));
        $end_date = strtotime("+" . ($to_day + 1) . " days", strtotime(date("Y-m-d"))) - 1;

        $query->where('match_date', [$start_date, $end_date], 'BETWEEN');

        // Fetch matches from DB
        $matches = $query->all();

        $grouped_matches = [];

        foreach ($matches as $match) {
            $match_data = json_decode($match['match_data'], true);

            $match_odds = $match['match_odds'] ? json_decode($match['match_odds'], true) : [];

            $league_id = $match_data['league']['id'];

            // Initialize league group if not already set
            if (!isset($grouped_matches[$league_id])) {
                $grouped_matches[$league_id] = [
                    'league' => $match_data['league'],
                    'matches' => []
                ];
            }

            // Prepare match data
            $match_info = [
                'match_id' => $match['match_id'],
                'home_team' => $match_data['home_team'],
                'away_team' => $match_data['away_team'],
                'home_score' => $match_data['home_score'],
                'away_score' => $match_data['away_score'],
                'match_date' => $match_data['match_date'],
                'status' => $match_data['status'],
                'fixture_status' => $match_data['fixture_status'],
                'current_minute' => $match['current_minute'],
                'current_second' => $match['current_second'],
                'odds' => $match_odds,
            ];

            // Add match to the appropriate league group
            $grouped_matches[$league_id]['matches'][] = $match_info;
        }

        return $grouped_matches;
    }

    /**
     * Get matches based on various filters
     * @param array $params
     * @return array
     */
    public function get_matches($params = []) {
        // $this->db->debug = true;

        $query = $this->db->from('matches');

        // Status filter
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        // League filter
        if (!empty($params['league'])) {
            $query->where('league_id', (int) $params['league']);
        }

        // Team filter (home or away)
        if (!empty($params['team_id'])) {
            $team_id = (int) $params['team_id'];
            $this->db->where('home_team_id', $params['team_id'], '=', '&&', true, 1);
            $this->db->where('away_team_id', $params['team_id'], '=', '||', true, 1);          
        }

        // Date range filter
        $from = null;
        $to = null;

        if (!empty($params['from']) && !empty($params['to'])) {
            $from = strtotime($params['from'] . ' 00:00:00');
            $to = strtotime($params['to'] . ' 23:59:59');
        } elseif (!empty($params['from']) && !empty($params['days'])) {
            $from = strtotime($params['from'] . ' 00:00:00');
            $to = strtotime("+{$params['days']} days", $from);
        } elseif (!empty($params['to']) && !empty($params['days'])) {
            $to = strtotime($params['to'] . ' 23:59:59');
            $from = strtotime("-{$params['days']} days", $to);
        }

        if ($from && $to) {
            $query->where('match_date', [$from, $to], 'BETWEEN');
        }

        // Limit (optional)
        if (!empty($params['limit'])) {
            $query->limit(0, (int) $params['limit']);
        }

        // Order by match date
        $query->orderBy('match_date', 'ASC');

        return $query->all();
    }

    /**
     * Save or update match details from API response.
     *
     * This method:
     * - Checks if the match (by fixture_id) already exists.
     * - Adds or updates the match in the database.
     * - Verifies and inserts home/away teams into the teams table if they don't exist.
     * - Generates and saves the match JSON data for list view.
     *
     * @param array $data API response for a single match
     * @return void
     */
    public function save_match($data) {
        $fixture_id = $data['fixture']['id'];
    
        // Check if match already exists
        $existing = $this->db->from('matches')->where('fixture_id', $fixture_id)->first();
    
        $teams = $data['teams'];
        $team_model = new Team();
    
        // Ensure both teams exist in DB
        $home_team = $team_model->upsert([
            'team_id' => $teams['home']['id'],
            'name'    => $teams['home']['name'],
            'logo'    => $teams['home']['logo'],
        ]);
    
        $away_team = $team_model->upsert([
            'team_id' => $teams['away']['id'],
            'name'    => $teams['away']['name'],
            'logo'    => $teams['away']['logo'],
        ]);
    
        $api_status_short = $data['fixture']['status']['short']; // e.g., "NS", "1H", "FT"
        $status_data = self::get_status_data($api_status_short);
        $status = $status_data['status'] ?? null;
    
        // Fetch country information
        $league_model = new League();
        $league = $league_model->get_by_id($data['league']['id']);
        $country = $this->db->from('countries')->where('country_id', $league['country'])->first();
    
        // Create match_data JSON
        $match_json = [
            'fixture_id'    => $fixture_id,
            'league'        => [
                'id' => $league['league_id'],
                'name' => $league['name'],
                'logo' => $league['logo'],
                'country' => [
                    'country_id' => $country['country_id'],
                    'name'       => $country['name'],
                    'code'       => $country['code'],
                    'flag'       => $country['flag'],
                ]
            ],
            'home_team'     => [
                'id' => $home_team['team_id'],
                'name' => $home_team['name'],
                'logo' => $home_team['logo'],
                'logo_icon_path' => $home_team['logo_icon_path'],
            ],
            'away_team'     => [
                'id' => $away_team['team_id'],
                'name' => $away_team['name'],
                'logo' => $away_team['logo'],
                'logo_icon_path' => $away_team['logo_icon_path'],
            ],
            'match_date'    => $data['fixture']['timestamp'],
            'status'        => $status,
            'fixture_status'=> $api_status_short,
            'home_score'      => $data['goals']['home'] ?? null,
            'away_score'      => $data['goals']['away'] ?? null,
        ];
    
        // Prepare match data
        $match_data = [
            'fixture_id'      => $fixture_id,
            'league_id'       => $league['league_id'],
            'match_date'      => $data['fixture']['timestamp'],
            'home_team_id'    => $home_team['team_id'],
            'away_team_id'    => $away_team['team_id'],
            'fixture_status'  => $api_status_short,
            'home_score'      => $data['goals']['home'] ?? null,
            'away_score'      => $data['goals']['away'] ?? null,
            'odds_added'      => $data['odds_added'] ?? 0,
            'match_data'      => json_encode($match_json),
            'match_odds'      => json_encode([]),
            'stats'           => json_encode([]),
            'status'          => $data['status'] ?? $status,
            'created_at'      => $data['created_at'] ?? time(),
        ];
    
        if ($existing) {
            $this->db->update('matches')->where('fixture_id', $fixture_id)->set($match_data);
        } else {
            $this->db->insert('matches')->set($match_data);
        }
    
    }

    /**
     * Get all fixture statuses with metadata
     *
     * @return array
     */
    public static function statuses(){
        return [
            'NS' => [
                'long' => 'Not Started',
                'status' => 'upcoming',
                'title' => [
                    'en' => 'Not Started',
                    'de' => 'Nicht gestartet'
                ],
            ],
            '1H' => [
                'long' => 'First Half',
                'status' => 'live',
                'title' => [
                    'en' => 'First Half',
                    'de' => '1. Hälfte'
                ],
                'match_ui' => [
                    'en' => '1. Half',
                    'de' => '1. Hälfte'
                ],
            ],
            '2H' => [
                'long' => 'Second Half',
                'status' => 'live',
                'title' => [
                    'en' => 'Second Half',
                    'de' => '2. Hälfte'
                ],
                'match_ui' => [
                    'en' => '2. Half',
                    'de' => '2. Hälfte'
                ],
            ],
            'HT' => [
                'long' => 'Half Time',
                'status' => 'live',
                'title' => [
                    'en' => 'Half Time',
                    'de' => 'Halbzeit'
                ],
                'match_ui' => [
                    'en' => 'Half Time',
                    'de' => 'Halbzeit'
                ],
            ],
            'FT' => [
                'long' => 'Full Time',
                'status' => 'completed',
                'title' => [
                    'en' => 'Full Time',
                    'de' => 'Vollzeit'
                ],
            ],
            'PST' => [
                'long' => 'Postponed',
                'status' => 'postponed',
                'title' => [
                    'en' => 'Postponed',
                    'de' => 'Verschoben'
                ],
            ],
            'CANC' => [
                'long' => 'Cancelled',
                'status' => 'cancelled',
                'title' => [
                    'en' => 'Cancelled',
                    'de' => 'Abgesagt'
                ],
            ],
            'SUSP' => [
                'long' => 'Suspended',
                'status' => 'suspended',
                'title' => [
                    'en' => 'Suspended',
                    'de' => 'Unterbrochen'
                ],
            ],
            'P' => [
                'long' => 'Penalty Shootout',
                'status' => 'live',
                'title' => [
                    'en' => 'Penalty Shootout',
                    'de' => 'Elfmeterschießen'
                ],
            ],
            'ET' => [
                'long' => 'Extra Time',
                'status' => 'live',
                'title' => [
                    'en' => 'Extra Time',
                    'de' => 'Verlängerung'
                ],
            ],
        ];
    }

    /**
     * Save match statistics
     *
     * @param int $fixture_id
     * @param array $stats
     */
    public function save_match_stats($fixture_id, $stats) {

        if (!empty($stats)) {

            $save = [];

            foreach ($stats as $team_index => $data) {
                foreach ($data['statistics'] as $stat) {
                    $save[$stat['type']][$team_index] = $stat['value'];
                }
            }


            $this->db->update('matches')
                ->where('fixture_id', $fixture_id)
                ->set([
                    'stats' => json_encode($save, JSON_UNESCAPED_UNICODE),
                    'updated_at' => time(),
                ]);
        }

    } 

    /**
     * Returns statistic types with labels
     *
     * @return array
     */
    public static function stat_types() {
        return [
            'Shots on Goal' => [
                'en' => 'Shots on Goal',
                'de' => 'Schüsse aufs Tor',
            ],
            'Shots off Goal' => [
                'en' => 'Shots off Goal',
                'de' => 'Schüsse neben das Tor',
            ],
            'Total Shots' => [
                'en' => 'Total Shots',
                'de' => 'Gesamtschüsse',
            ],
            'Blocked Shots' => [
                'en' => 'Blocked Shots',
                'de' => 'Geblockte Schüsse',
            ],
            'Shots insidebox' => [
                'en' => 'Shots Inside Box',
                'de' => 'Schüsse im Strafraum',
            ],
            'Shots outsidebox' => [
                'en' => 'Shots Outside Box',
                'de' => 'Schüsse außerhalb Strafraums',
            ],
            'Fouls' => [
                'en' => 'Fouls',
                'de' => 'Fouls',
            ],
            'Corner Kicks' => [
                'en' => 'Corner Kicks',
                'de' => 'Ecken',
            ],
            'Offsides' => [
                'en' => 'Offsides',
                'de' => 'Abseits',
            ],
            'Ball Possession' => [
                'en' => 'Ball Possession',
                'de' => 'Ballbesitz',
            ],
            'Yellow Cards' => [
                'en' => 'Yellow Cards',
                'de' => 'Gelbe Karten',
            ],
            'Red Cards' => [
                'en' => 'Red Cards',
                'de' => 'Rote Karten',
            ],
            'Goalkeeper Saves' => [
                'en' => 'Goalkeeper Saves',
                'de' => 'Torwartparaden',
            ],
            'Total passes' => [
                'en' => 'Total Passes',
                'de' => 'Pässe gesamt',
            ],
            'Passes accurate' => [
                'en' => 'Passes Accurate',
                'de' => 'Genaue Pässe',
            ],
            'Passes %' => [
                'en' => 'Passes %',
                'de' => 'Passgenauigkeit %',
            ],
            'expected_goals' => [
                'en' => 'Expected Goals',
                'de' => 'Erwartete Tore',
            ],
            'goals_prevented' => [
                'en' => 'Goals Prevented',
                'de' => 'Verhinderte Tore',
            ],
        ];
    }

    /**
     * Convert long status to short status code
     *
     * @param string $long
     * @return string|false
     */
    public static function long_to_short($long){
        $statuses = self::statuses();
        foreach ($statuses as $short => $status) {
            if (isset($status['long']) && strtolower($long) == strtolower($status['long'])) {
                return $short;
            }
        }
        return false;
    }

    /**
     * Get status data by short code
     *
     * @param string $short
     * @return array
     */
    public static function get_status_data($short) {
        $statuses = self::statuses();
        return $statuses[$short] ?? null;
    }

    /**
     * Get all match status types with titles and descriptions
     *
     * @return array
     */
    public static function get_status_types() {
        return [
            'upcoming' => [
                'title' => t('Upcoming'),
                'description' => t('Upcoming_Matches'),
            ],
            'live' => [
                'title' => t('Live'),
                'description' => t('Live_Matches'),
            ],
            'completed' => [
                'title' => t('Completed'),
                'description' => t('Finished_Matches'),
            ],
            'postponed' => [
                'title' => t('Postponed'),
                'description' => t('Postponed_Matches'),
            ],
            'cancelled' => [
                'title' => t('Cancelled'),
                'description' => t('Cancelled_Matches'),
            ],
            'suspended' => [
                'title' => t('Suspended'),
                'description' => t('Suspended_Matches'),
            ],
        ];
    }

    /**
     * Load match data without cache json data
     *
     * @param int $match_id The ID of the match.
     * @param string $odds  Default is 'FALSE'.
     * @return array|null The match data as an array or null if not found.
     */
    public function load($match_id, $odds = FALSE) {
        // Fetch match data
        $match = $this->db->from('matches')->where('match_id', $match_id)->first();
        if (!$match) return null;

        // Fetch related data (league and teams)
        $league = $this->db->from('leagues')->where('league_id', $match['league_id'])->first();
        $home_team = $this->db->from('teams')->where('team_id', $match['home_team_id'])->first();
        $away_team = $this->db->from('teams')->where('team_id', $match['away_team_id'])->first();
        $country = $this->db->from('countries')->where('country_id', $league['country'])->first();

        // Prepare base data (common for both list and full types)
        $data = [
            'fixture_id' => $match['fixture_id'],
            'league' => [
                'id' => $league['league_id'],
                'name' => $league['name'],
                'logo' => $league['logo'],
                'country' => [
                    'country_id' => $country['country_id'],
                    'name'       => $country['name'],
                    'code'       => $country['code'],
                    'flag'       => $country['flag'],
                ]
            ],
            'home_team' => [
                'id' => $home_team['team_id'],
                'name' => $home_team['name'],
                'logo' => $home_team['logo'],
                'logo_icon_path' => $home_team['logo_icon_path'],
            ],
            'away_team' => [
                'id' => $away_team['team_id'],
                'name' => $away_team['name'],
                'logo' => $away_team['logo'],
                'logo_icon_path' => $away_team['logo_icon_path'],
            ],
            'match_date' => $match['match_date'],
            'status' => $match['status'],
            'fixture_status' => $match['fixture_status'],
            'home_score' => $match['home_score'],
            'away_score' => $match['away_score'],
        ];

        if ($odds) {
            $data['odds'] = json_decode($match['match_odds'], TRUE);
        }

        return $data;
    }

    /**
     * Update live odds for matches
     * @param array $live_odds
     */
    public function update_live_odds($live_odds) {

        if (empty($live_odds)) return;

        foreach ($live_odds as $match) {

            $fixture_id = $match['fixture']['id'];

            if (!isset($match['bookmakers'][0]['bets'])) {
                continue; // No odds available
            }

            $odds_data = [];

            foreach ($match['bookmakers'][0]['bets'] as $bet) {
                $bet_type_id = BetType::get_id_by_api_key($bet['name']);
                if (!$bet_type_id) continue;

                foreach ($bet['values'] as $value) {
                    $odds_data[$bet_type_id][$value['value']] = (float)$value['odd'];
                }
            }

            if (!empty($odds_data)) {
                $this->db->update('matches')
                ->where('fixture_id', $fixture_id)
                ->set([
                    'odds' => json_encode($odds_data),
                    'updated_at' => time(),
                ]);
            }
        }
    }

    /**
     * Update live match status (from live matches)
     * @param array $live_matches
     */
    public function update_live_matches_status($live_matches) {

        if (empty($live_matches)) return;

        foreach ($live_matches as $match) {

            $fixture_id = $match['fixture']['id'];

            $this->db->update('matches')
                ->where('fixture_id', $fixture_id)
                ->set([
                    'status' => 'live',
                    'fixture_status' => $match['fixture']['status']['short'] ?? '',
                    'home_score' => $match['goals']['home'] ?? 0,
                    'away_score' => $match['goals']['away'] ?? 0,
                    'current_minute' => $match['fixture']['minute'] ?? 0,
                    'current_second' => $match['fixture']['second'] ?? 0,
                    'updated_at' => time(),
                ]);
        }
    }

}

?>
