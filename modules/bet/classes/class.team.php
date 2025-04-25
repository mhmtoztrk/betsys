<?php

class Team {
    private $db;

    public function __construct() {
        $this->db = new DB();
        $this->f = new File();
    }

    /**
     * Create a new team
     * @param array $vars
     * @return int
     */
    public function create($vars) {
        $vars['created_at'] = $vars['created_at'] ?? time();
        $vars['status'] = $vars['status'] ?? 'active';

        if (!empty($vars['logo'])) {
            $logo_file = $this->f->save_image_from_url($vars['logo'], [
                'path' => 'all/teams'
            ]);
            $vars['logo'] = $logo_file['fid'] ?? null;
            $vars['logo_icon_path'] = img_by_fid($logo_file['fid'], 'team_thumb') ?? NULL;
        }

        $this->db->insert('teams')->set($vars);
        return $this->db->lastId();
    }

    /**
     * Get team by ID
     * @param int $team_id
     * @return array|null
     */
    public function get_by_id($team_id) {
        return $this->db->from('teams')->where('team_id', $team_id)->first();
    }

    /**
     * Get all teams, optionally filtered by parameters
     * @param array $params (e.g. ['country' => 'England'])
     * @return array
     */
    public function get_all($params = []) {
        $query = $this->db->from('teams')->where('status', 'active');

        if (!empty($params['country'])) {
            $query->where('country', $params['country']);
        }

        return $query->all();
    }

    /**
     * Update team data by ID
     * @param int $team_id
     * @param array $data
     * @return void
     */
    public function update($team_id, $data) {
        $this->db->update('teams')->where('team_id', $team_id)->set($data);
    }

    /**
     * Insert or update a team by team_id
     *
     * @param array $data
     * @return int team_id
     */
    public function upsert($data) {
        $team = $this->get_by_id($data['team_id']);

        if(!$team) {
            $this->create($data);
            $team = $this->get_by_id($data['team_id']);
        }

        return $team;
    }

}

?>