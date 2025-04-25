<?php

class Bookmaker {
    private $db;

    public function __construct() {
        $this->db = new DB();
    }

    public function upsert($data) {
        $exists = $this->db->from('bookmakers')->where('bookmaker_id', $data['bookmaker_id'])->first();

        if (!$exists) {
            $data['created_at'] = time();
            $data['status'] = 'active';
            $this->db->insert('bookmakers')->set($data);
        }
    }

}
