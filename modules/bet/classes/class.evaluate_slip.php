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
}

?>