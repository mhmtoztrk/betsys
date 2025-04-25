<?php

class BetApi {
    private $apikey = '4fe170767d63c8ccf812a984a46ba695';
    private $baseUrl = 'https://v3.football.api-sports.io';
    private $bookmaker_id = 1;

    /**
     * Send a request to the API
     * @param string $endpoint
     * @param array $params
     * @return mixed
     */
    private function request($endpoint, $params = []) {
        $url = $this->baseUrl . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
    
        $headers = [
            "x-rapidapi-key: " . $this->apikey,
            "x-rapidapi-host: v3.football.api-sports.io"
        ];
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
        $result = curl_exec($ch);
        curl_close($ch);
    
        return json_decode($result, true);
    }

    /**
     * Sync matches for a specific day ahead.
     *
     * This method:
     * - Retrieves active leagues from the local database.
     * - Fetches fixtures from the API for each active league for the specified day.
     * - Saves or updates the match data in the database.
     *
     * @param int $day Number of days ahead to fetch matches (default is 0 - today)
     * @return void
     */
    public function sync_upcoming_matches($day = 0) {
        $league_model = new League();
        $match_model = new Matches();

        // Get active leagues from DB
        $active_leagues = $league_model->get_active_season_leagues();

        foreach ($active_leagues as $league) {
            // Calculate the date range for the given day
            $from_date = date("Y-m-d", strtotime("+$day days"));
            $to_date = date("Y-m-d", strtotime("+$day days"));

            $params = [
                'league' => $league['league_id'],
                'season' => $league['season'],
                'from'   => $from_date,
                'to'     => $to_date
            ];

            // Fetch matches for the specific day from API
            $response = $this->get_upcoming_matches($params);
            // pr($league);
            // pr($params);
            // pr($response);

            if (!empty($response)) {
                foreach ($response as $match_data) {
                    $match_model->save_match($match_data);
                }
            }
        }
    }

    /**
     * Get upcoming matches based on given parameters.
     * Supports filtering by days, date range, and league.
     *
     * @param array $params
     * @return mixed
     */
    public function get_upcoming_matches($params = []) {
        $query = [];
        
        // If 'from' and 'to' are provided, use them directly
        if (!empty($params['from']) && !empty($params['to'])) {
            $query['from'] = date("Y-m-d", strtotime($params['from']));
            $query['to'] = date("Y-m-d", strtotime($params['to']));
        }
        // If 'days' is provided, calculate the date range
        elseif (!empty($params['days'])) {
            $start_date = date("Y-m-d");
            $end_date = date("Y-m-d", strtotime("+{$params['days']} days"));
            $query['from'] = $start_date;
            $query['to'] = $end_date;
        }
        // If 'date' is provided, use that specific date
        elseif (!empty($params['date'])) {
            $query['date'] = date("Y-m-d", strtotime($params['date']));
        }
        // If 'next' is provided and no date range is set, use 'next' parameter
        elseif (!empty($params['next'])) {
            $query['next'] = (int) $params['next'];
        }

        // Add league filter if provided
        if (!empty($params['league'])) {
            $query['league'] = (int) $params['league'];
        }

        // Add season filter if provided
        if (!empty($params['season'])) {
            $query['season'] = (int) $params['season'];
        }

        $result = $this->request("/fixtures", $query);
        return $result['response'] ?? []; // Only return matches
    }

    /**
     * Sync odds for upcoming matches based on a given day offset.
     * 
     * @param int $day_offset Number of days ahead to fetch odds (default is 1)
     * @param int $bookmaker_id Specific bookmaker to fetch odds for (optional)
     * @return void
     */
    public function sync_odds($day = 0) {
        $league_model = new League();
        $match_model = new Matches();
        $odds_model = new Odds();
        $odds_model->bookmaker_id = $this->bookmaker_id;

        // Get active leagues from DB
        $active_leagues = $league_model->get_active_season_leagues();
        $date = date("Y-m-d", strtotime("+$day days"));

        foreach ($active_leagues as $league) {
            $params = [
                'league' => $league['league_id'],
                'season' => $league['season'],
                'date' => $date,
                'bookmaker' => $this->bookmaker_id
            ];

            // Fetch odds from API
            $odds_pages = $this->get_odds($params);

            foreach ($odds_pages as $odds_data) {
                $fixture_id = $odds_data['fixture_id'];
                $match = $match_model->get($fixture_id, 'fixture_id');

                if ($match) {
                    echo "Saving odds for match_id: {$match['match_id']} (fixture_id: $fixture_id)\n";
                    
                    // Deletes if there are odds for this match
                    $odds_model->delete_by_match($match['match_id']);
                    
                    // Save odds
                    $odds_model->save_odds($match['match_id'], $odds_data);
                } else {
                    echo "Match not found for fixture_id: $fixture_id. Skipping...\n";
                }
            }
        }
    }
    
    /**
     * Get odds data from API by fixture or league.
     * Uses a single bookmaker ID set in the class.
     *
     * @param array $params [fixture_id] or [league] required
     * @return array
     */
    public function get_odds($params = []) {
        $endpoint = '/odds';
        $results = [];

        // If fixture_id is provided, fetch odds for specific fixture
        if (!empty($params['fixture_id'])) {
            $query = [
                'fixture' => $params['fixture_id'],
                'bookmaker' => $this->bookmaker_id,
            ];
            $response = $this->request($endpoint, $query);
            return $response['response'][0]['bookmakers'] ?? [];
        }

        // If league is provided, fetch odds by league and season
        if (!empty($params['league']) && !empty($params['date'])) {
            $league_id = (int) $params['league'];
            
            // Automatically get active season from League class
            $league_model = new League();
            $season = $league_model->get_active_season($league_id);

            if (!$season) return [];

            $page = 1;
            $total_pages = 1;

            do {
                $query = [
                    'league'    => $league_id,
                    'season'    => $season,
                    'date'      => $params['date'], // Date in YYYY-MM-DD format
                    'bookmaker' => $this->bookmaker_id,
                    'page'      => $page
                ];

                $response = $this->request($endpoint, $query);
                $items = $response['response'] ?? [];

                foreach ($items as $item) {
                    $results[] = [
                        'fixture_id' => $item['fixture']['id'],
                        'bookmakers' => $item['bookmakers']
                    ];
                }

                // Total page info
                $total_pages = $response['paging']['total'] ?? 1;
                $page++;
            } while ($page <= $total_pages);
        }

        return $results;
    }
    
    /**
     * Get match statistics
     * @param int $fixture_id
     * @return array
     */
    public function get_match_stats($fixture_id) {
        $request = $this->request('/fixtures/statistics', [
            'fixture' => $fixture_id
        ]);
        
        return $request['response'] ?? [];
    }

    /**
     * Get live odds from API
     * @return array
     */
    public function get_live_odds() {
        $request = $this->request('/odds/live', []);
        return $request['response'];
    }

    /**
     * Get live matches from API
     * @return array
     */
    public function get_live_matches() {
        $request = $this->request('/fixtures', [
            'live' => 'all'
        ]);
        return $request['response'];
    }

    /**
     * Get league list
     * @return mixed
     */
    public function get_leagues() {
        return $this->request('/leagues');
    }
}

?>
