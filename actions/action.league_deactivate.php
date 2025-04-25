<?php

namespace Action;

class league_deactivate
{

    public $league = NULL;
    public $multi_type = 'single';
    public $league_ids = [];
    public $l;

    public function __construct($pars){
        $this->l = new \League();

        if (isset($pars['ids'])){

            $this->multi_type = 'bulk';
            $this->league_ids = $pars['ids'];

        }elseif (isset($pars['league_id'])){

            $league = $this->l->get_by_id($pars['league_id']);
            if ($league) $this->league = $league;

            $this->multi_type = 'single';
            $this->league_ids = [$pars['league_id']];

        }
    }

    public function confirmation(){
        if ($this->multi_type == 'bulk'){
            return [
                'title' => t('Deactivate_League'),
                'desc' => '',
                'apply_button' => [t('Deactivate'),'danger'],
            ];
        }else{
            return [
                'title' => $this->league['name'].' - '.t('Deactivate'),
                'desc' => '',
                'apply_button' => [t('Deactivate'),'danger'],
            ];
        }
    }

    public function access(){
        $access = TRUE;
        if (role_access('admin')){
            if ($this->multi_type == 'bulk'){
                $league_ids = $this->league_ids;

                foreach($league_ids as $league_id){
                    $league = $this->l->get_by_id($league_id);
                    if (!$league) $access = FALSE;
                }
            }else{
                if (!$this->league) $access = FALSE;
            }

        }else{
            $access = FALSE;
        }

        return $access;
    }

    public function apply(){
        $lui = new \BetUi\League();

        $league_ids =$this->league_ids;

        foreach ($league_ids as $league_id){
            $this->l->deactivate($league_id);

            $league = $this->l->get_by_id($league_id);

            \JS::live('replace_with', [
                'element' => '#league-'.$league_id,
                'new_html' => $lui->render_league($league),
            ]);

        }

        if ($this->multi_type == 'bulk'){

            \JS::live('message', [
                'type' => 'danger',
                'message' => t('Leagues_deactivated'),
            ]);

        }else{

            \JS::live('message', [
                'type' => 'danger',
                'message' => t('League_deactivated'),
            ]);

        }

        \JS::live('close_popup');

    }

}