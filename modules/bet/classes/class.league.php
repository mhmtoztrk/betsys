<?php

class League {
    private $db;

    public function __construct() {
        $this->db = new DB();
        $this->f = new File();
    }

    /**
     * Create a new league
     * @param array $vars
     * @return int
     */
    public function create($vars) {
        $vars['created_at'] = $vars['created_at'] ?? time();
        $vars['status'] = $vars['status'] ?? 'active';

        if (!empty($vars['logo'])) {
            $logo_file = $this->f->save_image_from_url($vars['logo'], [
                'path' => 'all/leagues'
            ]);
            $vars['logo'] = $logo_file['fid'] ?? null;
        }

        $this->db->insert('leagues')->set($vars);
        return $this->db->lastId();
    }

    /**
     * Get league by ID
     * @param int $league_id
     * @return array|null
     */
    public function get_by_id($league_id) {
        return $this->db->from('leagues')->where('league_id', $league_id)->first();
    }

    /**
     * Get all leagues, optionally filtered by parameters
     * @param array $params (e.g. ['country_id' => 5])
     * @return array
     */
    public function get_all($params = []) {
        $query = $this->db->from('leagues');

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (!empty($params['country_id'])) {
            $query->where('country', $params['country_id']);
        }

        $order_by = $params['order_by'] ?? 'name';
        $order_way = $params['order_way'] ?? 'ASC';
        $query->orderBy($order_by, $order_way);

        return $query->all();
    }

    /**
     * Get all leagues grouped by country
     * @return array
     */
    public function all_grouped_by_country($params = []) {
        
        $cnt  = new Country();
        $countries = $cnt->get_all();

        $list = [];
        foreach ($countries as $country) {
            $list[$country['country_id']] = $country;
        }

        $query = $this->db->from('leagues');

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $all = $query->all();
        foreach ($all as $league) {
            $list[$league['country']]['leagues'][$league['league_id']] = $league;
        }

        return $list;

    }

    /**
     * Update league data by ID
     * @param int $league_id
     * @param array $data
     * @return void
     */
    public function update($league_id, $data) {
        $this->db->update('leagues')->where('league_id', $league_id)->set($data);
    }

    /**
     * Create or update a league based on league_id
     * @param array $data
     * @return void
     */
    public function upsert($data) {
        $existing = $this->get_by_id($data['league_id']);

        if ($existing) {
            $this->update($data['league_id'], $data);
        } else {
            $this->create($data);
        }
    }

    /**
     * Get leagues where current time is within the season range
     * @return array
     */
    public function get_active_season_leagues() {
        $now = time();

        $all = $this->db->from('leagues')
            ->where('status', 'active')
            ->where('start_date', $now, '<=')
            ->where('end_date', $now, '>=')
            ->all();

        $list = [];
        foreach ($all as $item) {
            $list[$item['league_id']] = $item;
        }

        return $list;
    }

    /**
     * Get active season for a specific league
     * @param int $league_id
     * @return int|null
     */
    public function get_active_season($league_id) {
        $now = time();

        $row = $this->db->from('leagues')
            ->where('league_id', $league_id)
            ->where('status', 'active')
            ->where('start_date', $now, '<=')
            ->where('end_date', $now, '>=')
            ->first();

        return $row ? (int) $row['season'] : null;
    }

    /**
     * Activate a league
     * @param int $league_id
     * @return bool
     */
    public function activate($league_id) {
        return $this->db->update('leagues')
            ->where('league_id', $league_id)
            ->set(['status' => 'active']);
    }

    /**
     * Deactivate a league
     * @param int $league_id
     * @return bool
     */
    public function deactivate($league_id) {
        return $this->db->update('leagues')
            ->where('league_id', $league_id)
            ->set(['status' => 'passive']);
    }

}

?>