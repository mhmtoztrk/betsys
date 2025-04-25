<?php

class Cron {
    private $dbc;
    private $bt;
    private $bs;
    private $m;

    public function __construct() {
        $this->dbc = new DB();
        $this->bt = new BetApi();
        $this->bs = new BetSlip();
        $this->m = new Matches();
    }

    public function delete_sessions(){

        $this->dbc->delete('z_sessions')
        ->where('expires', time(), '<')
        ->done();
        
    }

    public function delete_form_sessions(){

        $this->dbc->delete('z_form_sessions')
        ->where('expires', time(), '<')
        ->done();

    }

    public function delete_form_submits(){

        //older than 10 days
        $this->dbc->delete('z_form_submits')
        ->where('created', time() - 864000, '<')
        ->done();
        
    }

    public function delete_tokens(){

        $this->dbc->delete('z_tokens')
        ->where('expires', time(), '<')
        ->done();
        
    }

    public function delete_old_matches(){

        //older than 60 days
        $this->dbc->delete('matches')
        ->where('created_at', time() - 5184000, '<')
        ->done();

    }

    public function get_upcoming_matches(){
        $this->bt->sync_upcoming_matches(3);
    }

    public function get_upcoming_odds(){
        $this->bt->sync_odds(3);
    }

    /**
     * Update live odds via cronjob
     */
    public function update_live_odds() {

        $live_odds = $this->bt->get_live_odds();
        $this->m->update_live_odds($live_odds);

    }

    /**
     * Update live matches status via cronjob
     */
    public function update_match_status() {

        $live_matches = $this->bt->get_live_matches();
        $this->m->update_live_matches_status($live_matches);

    }


    public function update_active_slips_status(){

        $this->bs->update_all_not_completed_slip();
        
    }
    
    public function update_match_stats() {

        $matches = $this->dbc->from('matches')
            ->select(['match_id', 'fixture_id'])
            ->where('status', 'live')
            ->all();
    
        if (!$matches) {
            return;
        }
    
        foreach ($matches as $match) {
            $stats = $this->bt->get_match_stats($match['fixture_id']);
            $this->m->save_match_stats($match['fixture_id'], $stats);
        }
    }    

    public function evaluate_slips(){

        $this->bs->evaluate_all_pending_slips();

    }

    public function revert_expired_submits() {
        $this->bs->check_all_submitted();
    }    

    public function update_leagues() {
        #
    }
    
}