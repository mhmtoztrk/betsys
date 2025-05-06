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
        $texts = $data['texts'] ?? NULL;
        unset($data['texts']);

        $data['bet_type_id'] = self::type_id($data['bet_type_id'], $data['odd_type']);
        $data['evaluate_method'] = $data['evaluate_method'] ?? 0;
        $data['status'] = $data['status'] ?? 'passive';
        $data['created_at'] = $data['created_at'] ?? time();

        $this->db->insert('bet_types')->set($data);

        if ($texts) {
            foreach ($texts as $lang => $lang_texts) {
                $this->create_text($data['bet_type_id'], $lang, $lang_texts);
            }
        }

        $this->create_caches();

        return $data['bet_type_id'];
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

    public function get_list($type){
        $c = new Cache();
        return $c->get('bet_types/'.$type);
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
        
        $this->create_caches();
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
        $this->update($bet_type_id, ['status' => 'active']);
        $this->create_caches();
    }

    /**
     * Deactivate a bet type
     */
    public function deactivate($bet_type_id) {
        $this->update($bet_type_id, ['status' => 'passive']);
        $this->create_caches();
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
        $query->select('bet_types.*, bet_type_texts.lang, bet_type_texts.name, bet_type_texts.description');
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
            $type['texts'] = [
                'name' => $type['name'],
                'description' => $type['description']
            ];
            unset($type['name'], $type['description']);

            $result[] = $type;
        }

        return $result;
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
        $this->create_caches();
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

    public static function type_id($id, $odd_type){
        $odd_type = strtolower($odd_type);
        $alias = $odd_type === 'pre_match' ? 'p' : 'l';
        return $alias . sprintf('%03d', $id);
    }

    public function create_caches(){

        $c = new Cache();

        foreach (ACTIVE_LANGS as $lang) {
            $types = $this->get_all_with_texts($lang);
            $c->save($types, 'bet_types/all_'.$lang);

            $pre_match_actives = $live_actives = [];

            foreach ($types as $type) {
                if ($type['status'] == 'active' && $type['evaluate_method']) {
                    $item = [
                        'name' => $type['texts']['name'],
                        'nadescriptionme' => $type['texts']['description'],
                    ];
                    if ($type['odd_type'] == 'pre_match') {
                        $pre_match_actives[$type['bet_type_id']] = $item;
                    }elseif ($type['odd_type'] == 'live') {
                        $live_actives[$type['bet_type_id']] = $item;
                    }
                }
            }
            
            $c->save($pre_match_actives, 'bet_types/pre_match_actives_'.$lang);
            $c->save($live_actives, 'bet_types/live_actives_'.$lang);
        }
        
    }
}

?>
