<?php

class BetType {
    private $db;

    public function __construct() {
        $this->db = new DB();
    }

    /**
     * Create a new bet type
     */
    public function create($data) {
        $type_data['api_key'] = $data['api_key'];
        $type_data['created_at'] = time();
        $type_data['status'] = $data['status'] ?? 'active';

        $this->db->insert('bet_types')->set($type_data);
        $bet_type_id = $this->db->lastId();

        if (!empty($data['texts'])) {
            foreach ($data['texts'] as $lang => $lang_texts) {
                $this->create_text($bet_type_id, $lang, $lang_texts);
            }
        }

        return $bet_type_id;
    }

    /**
     * Create or update bet type text
     */
    private function create_text($bet_type_id, $lang, $texts) {
        $data = [
            'bet_type_id' => $bet_type_id,
            'lang' => $lang,
            'name' => $texts['name'],
            'description' => $texts['description'] ?? null,
            'created_at' => time(),
        ];
        $this->db->insert('bet_type_texts')->set($data);
    }

    /**
     * Create or update bet type text
     */
    public function get_text($bet_type_id, $lang) {
        $text = $this->db->from('bet_type_texts')
        ->select(['name'])
        ->where('bet_type_id',$bet_type_id)
        ->where('lang',$lang)
        ->first();
        return $text['name'] ?? FALSE;
    }

    /**
     * Update an existing bet type
     */
    public function update($bet_type_id, $data) {

        if (!empty($data['texts'])) {
            foreach ($data['texts'] as $lang => $texts) {
                $this->update_text($bet_type_id, $lang, $texts);
            }
            unset($data['texts']);
        }

        $data['updated_at'] = time();
        $this->db->update('bet_types')->where('bet_type_id', $bet_type_id)->set($data);
    }

    /**
     * Update bet type text for a given language and bet type ID
     * @param int $bet_type_id Bet type ID (required)
     * @param string $lang Language code (required)
     * @param array $text Associative array with 'name' and/or 'description'
     * @return bool Update status (true on success, false on failure)
     */
    public function update_text($bet_type_id, $lang, $texts) {
        // Prepare data array
        $data = [];
        
        // Add only provided fields to the update array
        if (!empty($texts['name'])) {
            $data['name'] = $texts['name'];
        }
        if (!empty($texts['description'])) {
            $data['description'] = $texts['description'];
        }
        
        // If no fields to update, return false
        if (empty($data)) {
            return false;
        }

        // Add updated_at field
        $data['updated_at'] = time();

        // Update the bet type in the database
        $this->db->update('bet_types')
            ->where('bet_type_id', $bet_type_id)
            ->set([
                'updated_at' => time()
            ]);

        // Update the bet type text in the database
        return $this->db->update('bet_type_texts')
            ->where('bet_type_id', $bet_type_id)
            ->where('lang', $lang)
            ->set($data);
    }

    /**
     * Activate a bet type
     */
    public function activate($bet_type_id) {
        return $this->update($bet_type_id, ['status' => 'active']);
    }

    /**
     * Deactivate a bet type
     */
    public function deactivate($bet_type_id) {
        return $this->update($bet_type_id, ['status' => 'passive']);
    }

    /**
     * Get all bet types
     */
    public function get_all($status = null) {
        $query = $this->db->from('bet_types');
        if ($status) {
            $query->where('status', $status);
        }
        return $query->all();
    }

    /**
     * Get all bet types with their texts for a specific language
     * @param string $lang Language code (required)
     * @param string|null $status Status of bet type (active/passive) (optional)
     * @return array List of bet types with texts
     */
    public function get_all_with_texts($lang, $status = null) {
        // Initialize query to get all bet types with texts using JOIN
        $query = $this->db->from('bet_types');
        $query->leftJoin('bet_type_texts', '%s.bet_type_id = %s.bet_type_id');
        $query->select('*');
        $query->where('lang', $lang);

        // Filter by status if provided
        if ($status) {
            $query->where('status', $status);
        }

        // Fetch all joined bet types and texts
        $bet_types = $query->all();
        // pr($bet_types);

        // Prepare result array with texts
        $result = [];
        foreach ($bet_types as $type) {
            $result[] = [
                'bet_type_id' => $type['bet_type_id'],
                'api_key' => $type['api_key'],
                'status' => $type['status'],
                'created_at' => $type['created_at'],
                'texts' => [
                    'name' => $type['name'],
                    'description' => $type['description']
                ],
            ];
        }

        return $result;
    }

    /**
     * Get the mapping of bet types from the database.
     * 
     * This method retrieves all active bet types from the database and returns a mapping array
     * where the keys are the bet type names (as received from the API) and the values are the 
     * corresponding bet type IDs from the local system. This map is used to efficiently translate 
     * API bet type names to local database IDs without performing multiple queries.
     * 
     * The method uses a static variable to store the map after the first call, 
     * ensuring that the database is only queried once per request.
     * 
     * Example returned array:
     * [
     *     'Match Winner' => 1,
     *     'Goals Over/Under' => 2,
     *     'Double Chance' => 3,
     * ]
     * 
     * @return array Associative array where keys are bet type names and values are bet type IDs.
     */
    public static function get_bet_type_map() {
        // Static cache to avoid repeated DB queries
        static $bet_type_map = null;
    
        // If the map is already generated, return it
        if ($bet_type_map !== null) {
            return $bet_type_map;
        }
    
        // Initialize DB instance
        $db = new DB();
    
        // Fetch all bet types from the database
        $bet_types = $db->from('bet_types')->select(['api_key', 'bet_type_id'])->all();
    
        // Prepare the map: api_key => bet_type_id
        $bet_type_map = [];
        foreach ($bet_types as $type) {
            $bet_type_map[$type['api_key']] = $type['bet_type_id'];
        }
    
        return $bet_type_map;
    }    

    /**
     * Get a single bet type by ID with text in the specified language
     * @param int $bet_type_id Bet type ID (required)
     * @param string $lang Language code (optional)
     * @return array|null Bet type with name and description, or null if not found
     */
    public function get_by_id($bet_type_id, $lang = null) {
        // Query to get the bet type with text in the specified language
        $query = $this->db->from('bet_types');
        $query->where('bet_types.bet_type_id', $bet_type_id);
        
        if($lang){
            // Fetch the bet type with language
            $query->leftJoin('bet_type_texts', '%s.bet_type_id = %s.bet_type_id');
            $query->where('bet_type_texts.lang', $lang);
            $bet_type = $query->first();
            $bet_type['texts'] = [
                'name' => $bet_type['name'],
                'description' => $bet_type['description'],
            ];
            unset($bet_type['name'], $bet_type['description']);
        }else{
            $bet_type = $query->first();

            $query = $this->db->from('bet_type_texts');
            $query->where('bet_type_id', $bet_type_id);
            $texts = $query->all();
            foreach ($texts as $item) {
                $bet_type['texts'][$item['lang']]['name'] = $item['name'];
                $bet_type['texts'][$item['lang']]['description'] = $item['description'];
            }
        }

        return $bet_type;
    }

    /**
     * Delete a bet type
     */
    public function delete($bet_type_id) {
        $this->db->delete('bet_types')->where('bet_type_id', $bet_type_id)->done();
        $this->db->delete('bet_type_texts')->where('bet_type_id', $bet_type_id)->done();
    }

    /**
     * Submit function for bet type edit form
     */
    public function bet_type_form_submit($values, $form) {
        // pr($values);

        $save = [];

        $langs = all_langs();
        foreach ($langs as $lang => $lang_data) {

            $save['texts'][$lang]['name'] = $values[$lang.'_name'];
            $save['texts'][$lang]['description'] = $values[$lang.'_description'];

        }

        $this->update($values['bet_type_id'], $save);

        JS::st1(PANEL_PATH.'/bet-types', t('Bet_type_saved'));
    }
}

?>
