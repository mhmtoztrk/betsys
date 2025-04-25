<?php

class Country {
    private $db;

    public function __construct() {
        $this->db = new DB();
        $this->f = new File();
    }

    /**
     * Create a new country
     * @param array $vars
     * @return int
     */
    public function create($vars) {
        $vars['created_at'] = $vars['created_at'] ?? time();
        $vars['status'] = $vars['status'] ?? 'active';

        if (!empty($vars['flag'])) {
            $logo_file = $this->f->save_image_from_url($vars['flag'], [
                'path' => 'all/flags'
            ]);
            $vars['flag'] = $logo_file['fid'] ?? null;
        }

        $this->db->insert('countries')->set($vars);
        return $this->db->lastId();
    }

    /**
     * Get country by ID
     * @param int $country_id
     * @return array|null
     */
    public function get_by_id($country_id) {
        return $this->db->from('countries')->where('country_id', $country_id)->first();
    }

    /**
     * Get all countries
     * @param array $params (e.g. ['status' => 'active'])
     * @return array
     */
    public function get_all($params = []) {
        $query = $this->db->from('countries');

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $order_by = $params['order_by'] ?? 'name';
        $order_way = $params['order_way'] ?? 'ASC';
        $query->orderBy($order_by, $order_way);

        return $query->all();
    }

    /**
     * Update country data
     * @param int $country_id
     * @param array $data
     * @return void
     */
    public function update($country_id, $data) {
        $this->db->update('countries')->where('country_id', $country_id)->set($data);
    }

    /**
     * Create or update a country based on name
     * @param array $data
     * @return int country_id
     */
    public function upsert($data) {
        $existing = $this->db->from('countries')->where('name', $data['name'])->first();

        if ($existing) {
            $this->update($existing['country_id'], $data);
            return $existing['country_id'];
        } else {
            return $this->create($data);
        }
    }
}

?>
