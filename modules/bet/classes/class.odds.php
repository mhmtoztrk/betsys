<?php

class Odds {
    private $db;
    public $bookmaker_id;

    public function __construct() {
        $this->db = new DB();
    }

    /**
     * Get odds by match ID
     * @param int $match_id
     * @return array
     */
    public function get_by_match_id($match_id) {
        $odds = $this->db->from('odds')
        ->where('match_id', $match_id)
        ->orderBy('bet_type_id', 'ASC')
        ->all();

        // Group odds by bet type
        $grouped_odds = [];
        foreach ($odds as $odd) {
            $grouped_odds[$odd['bet_type_id']][$odd['value']] = [
                'odd_value' => $odd['odd_value'],
                'handicap' => $odd['handicap'],
                'suspended' => $odd['suspended'],
            ];
        }
        return $grouped_odds;
    }

    /**
     * Get odds by ID
     * @param int $odds_id
     * @return array|null
     */
    public function get_by_id($odds_id) {
        return $this->db->from('odds')->where('odds_id', $odds_id)->first();
    }

    /**
     * Save odds data for a given match ID
     *
     * @param int $match_id
     * @param array $odds_data
     * @return void
     */
    public function save_odds($match_id, $odds_data) {
        // Optional: clean previous odds
        $this->delete_by_match($match_id);
    
        $bet_type_map = BetType::get_bet_type_map();
    
        // Check if bookmakers data is valid
        if (empty($odds_data['bookmakers']) || !is_array($odds_data['bookmakers'])) {
            echo "No valid bookmakers found for match_id: $match_id\n";
            return;
        }
        
        $odds_array = [];
        foreach ($odds_data['bookmakers'] as $bookmaker) {
            $bookmaker_id = $this->bookmaker_id;
    
            // Check if bets data is valid
            if (empty($bookmaker['bets']) || !is_array($bookmaker['bets'])) {
                echo "No valid bets found for bookmaker_id: $bookmaker_id, match_id: $match_id\n";
                continue;
            }
    
            foreach ($bookmaker['bets'] as $bet) {
                $bet_type = $bet['name'] ?? null;

                if(isset($bet_type_map[$bet_type])){

                    $bet_type_id = $bet_type_map[$bet_type];

                }else{

                    //Create bet type if bet type is not exist in system
                    $langs = all_langs();

                    $texts = [];
                    foreach ($langs as $lang => $value) {
                        $texts[$lang] = [
                            'name' => $bet_type,
                        ];
                    }

                    $bt = new BetType();
                    $bet_type_id = $bt->create([
                        'api_key' => $bet_type,
                        'texts' => $texts,
                    ]);

                }
    
                if (!empty($bet['values']) && is_array($bet['values'])) {
                    foreach ($bet['values'] as $value) {

                        $odds_array[$bet_type_id][$value['value']] = [
                            'odd_value' => $value['odd'] ?? null,
                            'handicap' => $value['handicap'] ?? null,
                            'suspended' => $value['suspended'] ?? 0,
                        ];
                        
                        /*
                        $insert_data = [
                            'match_id'      => $match_id,
                            'bookmaker_id'  => $bookmaker_id,
                            'bet_type_id'   => $bet_type_id,
                            'value'         => $value['value'] ?? null,
                            'odd_value'     => $value['odd'] ?? null,
                            'handicap'      => $value['handicap'] ?? null,
                            'suspended'     => $value['suspended'] ?? 0,
                            'created_at'    => time(),
                            'status'        => 'active',
                        ];
    
                        // Validate essential fields before inserting
                        if (!is_null($insert_data['value']) && !is_null($insert_data['odd_value'])) {
                            $this->db->insert('odds')->set($insert_data);
                        }
                        */

                    }
                } else {
                    echo "No valid values for bet type: $bet_type in match_id: $match_id\n";
                }
            }
        }

        // Save the odds as JSON in the match_odds field and mark odds as added
        $this->db->update('matches')
        ->where('match_id', $match_id)
        ->set([
            'match_odds' => json_encode($odds_array),
            'odds_added' => 1,
            'bookmaker_id' => $bookmaker_id,
        ]);
        
        echo "Odds saved for match_id: $match_id\n";
    }

    /**
     * Delete odds by match_id
     * @param int $match_id
     * @return void
     */
    public function delete_by_match($match_id) {
        $this->db->delete('odds')->where('match_id', $match_id)->done();
        $this->db->update('matches')
        ->where('match_id',$match_id)
        ->set([
            'odds_added' => 0,
            'match_odds' => null,
        ]);
    }
}

?>
