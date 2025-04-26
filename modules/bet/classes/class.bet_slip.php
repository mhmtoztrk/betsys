<?php

class BetSlip {
    private $db;

    public function __construct() {
        $this->db = new DB();
    }

    /**
     * Create a new bet slip
     */
    public function create($data) {

        $data['total_stake'] = $data['total_stake'] ?? 0;
        $data['total_odds'] = $data['total_odds'] ?? 1;
        $data['potential_payout'] = $data['potential_payout'] ?? 0;
        $data['bets_count'] = $data['bets_count'] ?? 0;
        $data['bets'] = !empty($data['bets']) ? json_encode($data['bets']) : json_encode([]);
        
        $data['status'] = $data['status'] ?? 'open';
        $data['version'] = $data['version'] ?? 1;
        
        $data['created_at'] = time();

        $this->db->insert('bet_slips')->set($data);
        return $this->db->lastId();
        
    }

    /**
     * Create a new open slip for user
     * @param int $uid
     * @return int|false
     */
    public function create_open_slip($uid) {

        // Check if the user already has an open slip
        $user = $this->get_user_data($uid);
        if ($user['open_bet_slip']) {
            return $user['open_bet_slip'];
        }

        // Create a new slip
        $slip_id = $this->create([
            'uid' => $uid,
        ]);

        // Update user's open_bet_slip field
        $this->db->update('z_users')
        ->where('uid', $uid)
        ->set([
            'open_bet_slip' => $slip_id,
        ]);

        return $slip_id;

    }

    /**
     * load bet slip
     * @param int $slip_id
     * @param bool $bets loads slip bets (optional)
     * @return array
     */
    public function load($slip_id, $bets = TRUE) {

        $slip = $this->db->from('bet_slips')
        ->where('slip_id',$slip_id)
        ->first();

        if (!$slip) return false;

        $slip['bets'] = json_decode($slip['bets'], 1);
        
        return $slip;
    }

    /**
     * delete slip and slip bets
     * @param int $slip_id
     */
    public function delete($slip_id) {

        $this->db->delete('bet_slips')
        ->where('slip_id', $slip_id)
        ->done();
        
    }

    /**
     * load slip bets
     * @param int $slip_id
     * @return array
     */
    public function slip_bets($slip_id) {

        $slip = $this->db->from('bet_slips')
        ->select(['bets'])
        ->where('slip_id',$slip_id)
        ->first();

        return $slip['bets'] ? json_decode($slip['bets'], 1) : FALSE;
        
    }

    /**
     * create slip bet
     * @param int $slip_id
     * @param array $data, data of bet
     */
    public function create_bet($slip_id, $data) {

        $slip = $this->load($slip_id);
        $bets = $slip['bets'];

        $lang = $data['lang'] ?? DEF_LANG;

        $match_id = $data['match_id'];

        $bet_type_id = $data['bet_type_id'];
        $bet_value = $data['bet_value'];
        $odd_value = $data['odd_value'];
        
        if (!isset($bets[$match_id])) {
            $m = new Matches();
            $match = $m->get_full($match_id);
            // pr($match);
            $bets[$match_id] = [
                'name' => $match['home_team']['name'].' - '.$match['away_team']['name'],
                'league_id' => $match['league']['id'],
                'home_team_id' => $match['home_team']['id'],
                'away_team_id' => $match['away_team']['id'],
                'status' => 'active',
                'bets' => [],
                'current_when_confirmed' => [],
            ];
        }

        // Check if same bet_type already exists
        if (isset($bets[$match_id]['bets'][$bet_type_id])) {
            unset($bets[$match_id]['bets'][$bet_type_id]);
        }

        // Conflict control inside this match
        $conflicts = ConflictChecker::find_conflicts($bets[$match_id]['bets'], $bet_type_id);
        foreach ($conflicts as $conflict_bet_type_id) {
            unset($bets[$match_id]['bets'][$conflict_bet_type_id]);
        }

        $bt = new BetType();
        $bets[$match_id]['bets'][$bet_type_id] = [
            'name' => $bt->get_text($bet_type_id, $lang),
            'status' => 'active',
            'created_at' => time(),
            'bet_value' => $bet_value,
            'odd_value' => $odd_value,
        ];

        // Update slip odds
        $total_odds = $this->calculate_total_odds($bets);

        $this->db->update('bet_slips')
        ->where('slip_id',$slip_id)
        ->set([
            'total_odds' => $total_odds,
            'potential_payout' => $slip['total_stake'] * $total_odds,
            'bets_count' => $this->bets_count($bets),
            'bets' => json_encode($bets),
            'updated_at' => time(),
        ]);

        return [
            'status' => 1,
        ];
        
    }

    /**
     * Calculates the total odds of the slip
     *
     * @param array $bets
     * @return float
     */
    private function calculate_total_odds($bets) {
        $total = 1;
        foreach ($bets as $match) {
            foreach ($match['bets'] as $bet) {
                $total *= (float)$bet['odd_value'];
            }
        }
        return $total;
    }

    /**
     * counts bet
     * @param array $bets
     */
    public function bets_count($bets) {
        
        $count = 0;

        foreach ($bets as $match_id => $match_data) {
            $count += count($match_data['bets']);
        }

        return $count;
        
    }

    /**
     * checks match, odds etc current statuses and updates slip
     * @param mixed $slip
     */
    public function update_not_completed_slip($slip) {

        if (!is_array($slip)) $slip = $this->load($slip);
        $slip_id = $slip['slip_id'];
        
        $excluded = ['won', 'lost', 'cancelled'];
    
        if (!in_array($slip['status'], $excluded)) {
    
            $bets = $slip['bets'];
            $changed = false;
    
            $m = new Matches();
    
            foreach ($bets as $match_id => $bets_match_data) {
    
                $match = $m->get_full($match_id);
    
                // Match is finished or not available
                if (!in_array($match['status'], ['upcoming', 'live'])) {

                    unset($bets[$match_id]);
                    $changed = true;

                }else{
    
                    foreach ($bets_match_data['bets'] as $bet_type_id => $bet) {
    
                        if (!isset($match['odds'][$bet_type_id])) {
                
                            unset($bets[$match_id]['bets'][$bet_type_id]);
                            $changed = true;
                
                        }else{
                
                            $current_odd = (float)$match['odds'][$bet_type_id][$bet['bet_value']]['odd_value'];
                            $bet_odd = (float)$bet['odd_value'];

                            if (abs($current_odd - $bet_odd) > 0.001) {
                                $bets[$match_id]['bets'][$bet_type_id]['odd_value'] = $current_odd;
                                $changed = true;
                            }
                
                        }

                    }
        
                    // If no more bets left in match
                    if (empty($bets[$match_id]['bets'])) {
                        unset($bets[$match_id]);
                    }

                }
            }
    
            if ($changed) {
                $total_odds = $this->calculate_total_odds($bets);

                $update_data = [
                    'bets' => json_encode($bets),
                    'total_odds' => $total_odds,
                    'potential_payout' => $slip['total_stake'] * $total_odds,
                    'bets_count' => $this->bets_count($bets),
                    'version' => (int)$slip['version'] + 1,
                    'updated_at' => time(),
                ];
        
                // If slip was submitted, we reset it back to 'open'
                if ($slip['status'] == 'submitted') {
                    $update_data['status'] = 'open';
                    $update_data['submitted_at'] = NULL;
                }
        
                $this->db->update('bet_slips')
                    ->where('slip_id', $slip_id)
                    ->set($update_data);
            }
        }

    }
    
    /**
     * Updates all open slips
     */
    public function update_all_not_completed_slip() {

        $open_slips = $this->db->from('bet_slips')
        ->select(['slip_id', 'status', 'version', 'bets'])
        ->where('status', ['open', 'submitted', 'deactive'],'IN')
        ->all();

        if (!$open_slips) {
            return;
        }

        foreach ($open_slips as $slip) {
            $slip['bets'] = json_decode($slip['bets'], 1);
            $this->update_not_completed_slip($slip);
        }

    }

    /**
     * remove slip bet
     * @param int $slbid
     * @param int $match_id
     * @param int $bet_type_id
     */
    public function remove_bet($slip_id, $match_id, $bet_type_id) {

        $slip = $this->load($slip_id);
        $bets = $slip['bets'];
        
        unset($bets[$match_id]['bets'][$bet_type_id]);

        if (empty($bets[$match_id]['bets'])) {
            unset($bets[$match_id]);
        }

        $total_odds = $this->calculate_total_odds($bets);

        $bets_count = $this->bets_count($bets);

        if($bets_count > 0){
            $total_stake = $slip['total_stake'];
        }else{
            $total_stake = 0;
        }

        $this->db->update('bet_slips')
        ->where('slip_id',$slip_id)
        ->set([
            'total_stake' => $total_stake,
            'total_odds' => $total_odds,
            'potential_payout' => $total_stake * $total_odds,
            'bets_count' => $bets_count,
            'bets' => json_encode($bets),
            'updated_at' => time(),
        ]);
        
    }
    
    /**
     * Update stake amount of the bet slip
     *
     * @param array|int $slip Slip array or slip_id
     * @param float $stake Stake value
     * @return bool
     */
    public function update_stake($slip, $stake) {

        if (!is_array($slip)) {
            $slip = $this->load($slip);
        }

        $slip_id = $slip['slip_id'];

        $bets = $slip['bets'] ?? [];
        $total_odds = $slip['total_odds'] ?? 1;

        // Round and sanitize stake value
        $stake = round((float)$stake, 2);
        if ($stake < 0) $stake = 0;

        $potential_payout = $stake * $total_odds;

        $this->db->update('bet_slips')
            ->where('slip_id', $slip_id)
            ->set([
                'total_stake' => $stake,
                'potential_payout' => $potential_payout,
                'updated_at' => time(),
            ]);

        return true;
    }

    /**
     * bet slip ui texts for js to create bet slip block
     * @param string $lang
     * @return array
     */
    public function ui_texts($lang) {
        return [
            'en' => [
                'bet_slip_header' => 'Bet Slip',
                'stake' => 'Stake',
                'total_odds' => 'Total Odds',
                'potential_payout' => 'Potential Payout',
                'confirm_bet' => 'Confirm Bet',
                'clear_all' => 'Clear All',
                'no_bets_selected' => 'No Bets Selected',
                'something_went_wrong' => 'Something went wrong',
                'odd_not_active' => 'This odd is not active to bet now',
            ],
            'de' => [
                'bet_slip_header' => 'Bet Slip',
                'stake' => 'Stake',
                'total_odds' => 'Total Odds',
                'potential_payout' => 'Potential Payout',
                'confirm_bet' => 'Confirm Bet',
                'clear_all' => 'Clear All',
                'no_bets_selected' => 'No Bets Selected',
                'something_went_wrong' => 'Something went wrong',
                'odd_not_active' => 'This odd is not active to bet now',
            ]
        ][$lang];
    }

    /**
     * load user's data about bet
     * @param int $uid
     * @return array
     */
    public function get_user_data($uid) {

        return $this->db->from('z_users')
        ->select(['balance','open_bet_slip','total_stake','total_winnings'])
        ->where('uid',$uid)
        ->first();
        
    }

    /**
     * submits slip
     * @param mixed $slip
     * @return array
     */
    public function submit_slip($slip) {

        $slip = is_array($slip) ? $slip : $this->load($slip);
        $slip_id = $slip['slip_id'];

        // Check potential payout
        if ($slip['potential_payout'] > MAX_WINNINGS) {
            return [
                'status' => 0,
                'error' => 'max_winnings_exceeded',
            ];
        }
        
        $user = $this->get_user_data($slip['uid']);

        if ($slip['total_stake'] > $user['balance']) {
            return [
                'status' => 0,
                'error' => 'balance_exceeded',
            ];
        }

        $this->db->update('bet_slips')
        ->where('slip_id', $slip_id)
        ->set([
            'status' => 'submitted',
            'updated_at' => time(),
            'submitted_at' => time(),
        ]);

        return [
            'status' => 1,
        ];
    }

    /**
     * confirms slip
     * @param mixed $slip
     * @return array
     */
    public function confirm_slip($slip) {

        $slip = is_array($slip) ? $slip : $this->load($slip);
        $slip_id = $slip['slip_id'];

        // Check potential payout
        if ($slip['potential_payout'] > MAX_WINNINGS) {
            return [
                'status' => 0,
                'error' => 'max_winnings_exceeded',
            ];
        }

        $bets = $slip['bets'];

        $m = new Matches();

        foreach ($bets as $match_id => $match_data) {
            $match = $m->get($match_id);
            $bets[$match_id]['current_when_confirmed'] = [
                'status' => $match['status'],
                'fixture_status' => $match['fixture_status'],
                'home_score' => $match['home_score'],
                'away_score' => $match['away_score'],
                'minute' => $match['current_minute'],
                'second' => $match['current_second'],
            ];
        }

        $this->db->update('bet_slips')
        ->where('slip_id', $slip_id)
        ->set([
            'bets' => json_encode($bets),
            'status' => 'pending',
            'updated_at' => time(),
            'confirmed_at' => time(),
        ]);

        $user_data = $this->get_user_data($slip['uid']);
        $new_total_stake = $user_data['total_stake'] + $slip['total_stake'];

        $this->db->update('z_users')
        ->where('uid', $slip['uid'])
        ->set([
            'open_bet_slip' => NULL,
            'total_stake' => $new_total_stake,
        ]);

        $cr = new Credits();
        $cr->subtract_credit($slip['uid'], $slip['total_stake'], [
            'type' => 'bet',
            'slip_id' => $slip_id,
        ]);

        return [
            'status' => 1,
        ];
    }

    /**
     * Resets (cancels) an open slip manually
     * @param mixed $slip
     * @return bool
     */
    public function reset_slip($slip) {

        $slip = is_array($slip) ? $slip : $this->load($slip);

        $this->delete($slip['slip_id']);

        // Create a new slip
        $new_slip_id = $this->create([
            'uid' => $slip['uid'],
        ]);

        // Update user's open_bet_slip field
        $this->db->update('z_users')
        ->where('uid', $slip['uid'])
        ->set([
            'open_bet_slip' => $new_slip_id,
        ]);

        return $new_slip_id;

    }

    /**
     * evaluates slip status and sets as won, lost etc
     * @param mixed $slip
     * @return bool
     */
    public function evaluate_slip($slip) {

        if (!is_array($slip)) $slip = $this->load($slip);
        $slip_id = $slip['slip_id'];
        
        $bets = $slip['bets'];
        $m = new Matches();

        $status = 'won';
        foreach ($bets as $match_id => $match_data) {

            $match = $m->get($match_id);
            if($match['status'] != 'completed') return false;

            $ev = new EvaluateSlip($match);

            foreach ($match_data['bets'] as $bet_type_id => $bet) {
                
                $bet_status = $ev->check($bet_type_id, $bet);
                if($bet_status == 'lost'){
                    $status = 'lost';
                    break;
                }

            }

        }

        $this->db->update('bet_slips')
        ->where('slip_id',$slip_id)
        ->set([
            'status' => $status,
        ]);

        if($status == 'won'){
            $user_data = $this->get_user_data($slip['uid']);
            $new_winnings = $user_data['total_winnings'] + $slip['potential_payout'];

            $this->db->update('z_users')
            ->where('uid', $slip['uid'])
            ->set([
                'total_winnings' => $new_winnings,
            ]);

            $cr = new Credits();
            $cr->add_credit($slip['uid'], $slip['potential_payout'], [
                'type' => 'bet',
                'slip_id' => $slip_id,
            ]);
        }

        return true;
        
    }
    
    /**
     * Evaluate all pending slips
     */
    public function evaluate_all_pending_slips() {

        $pending_slips = $this->db->from('bet_slips')
        ->select(['slip_id', 'uid', 'bets', 'potential_payout'])
            ->where('status', 'pending')
            ->all();
    
        if (!$pending_slips) {
            return;
        }
    
        foreach ($pending_slips as $slip) {
            $this->evaluate_slip($slip);
        }

    }

    public function check_all_submitted() {
        $slips = $this->db->from('bet_slips')
            ->select(['slip_id', 'uid', 'bets', 'submitted_at', 'total_stake'])
            ->where('status', 'submitted')
            ->all();
    
        foreach ($slips as $slip) {

            if (time() - (int)$slip['submitted_at'] > 10) {

                $this->confirm_slip($slip);

            }
        }
    }
    
}

?>