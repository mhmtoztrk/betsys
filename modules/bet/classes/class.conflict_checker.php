<?php

class ConflictChecker {

    /**
     * Conflict list
     */
    private static $conflicts = [
        1  => [19, 48, 20, 3],
        19 => [1, 48],
        48 => [1, 19],
        5  => [6, 7],
        6  => [5],
        7  => [5],
        4  => [14],
        14 => [4],
        11 => [29, 30],
        29 => [11],
        30 => [11],
        28 => [19],
        32 => [33],
        33 => [32],
        20 => [1],
        3  => [1],
    ];

    /**
     * Check if two bet types conflict
     *
     * @param int $bet_type_a
     * @param int $bet_type_b
     * @return bool
     */
    public static function is_conflict($bet_type_a, $bet_type_b) {
        if (isset(self::$conflicts[$bet_type_a]) && in_array($bet_type_b, self::$conflicts[$bet_type_a])) {
            return true;
        }
        if (isset(self::$conflicts[$bet_type_b]) && in_array($bet_type_a, self::$conflicts[$bet_type_b])) {
            return true;
        }
        return false;
    }
    
    /**
     * Find conflicts inside match bets for a new bet type
     *
     * @param array $match_bets  // Only the match bets, not the whole slip
     * @param int $new_bet_type_id
     * @return array List of bet_type_id that conflict
     */
    public static function find_conflicts($match_bets, $new_bet_type_id) {
        $conflicts = [];
        foreach ($match_bets as $existing_bet_type_id => $bet_info) {
            if (self::is_conflict($existing_bet_type_id, $new_bet_type_id)) {
                $conflicts[] = $existing_bet_type_id;
            }
        }
        return $conflicts;
    }

}

?>
