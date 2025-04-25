<?php

namespace Action;

class bet_type_deactivate
{

    public $bet_type = NULL;
    public $multi_type = 'single';
    public $bet_type_ids = [];
    public $l;
    public $lang;

    public function __construct($pars){
        $this->bt = new \BetType();
        $this->lang = $pars['lang'];

        if (isset($pars['ids'])){

            $this->multi_type = 'bulk';
            $this->bet_type_ids = $pars['ids'];

        }elseif (isset($pars['bet_type_id'])){

            $bet_type = $this->bt->get_by_id($pars['bet_type_id'], $this->lang);
            if ($bet_type) $this->bet_type = $bet_type;

            $this->multi_type = 'single';
            $this->bet_type_ids = [$pars['bet_type_id']];

        }
    }

    public function confirmation(){
        if ($this->multi_type == 'bulk'){
            return [
                'title' => t('Deactivate_Bet_Type'),
                'desc' => '',
                'apply_button' => [t('Deactivate'),'danger'],
            ];
        }else{
            return [
                'title' => $this->bet_type['api_key'].' - '.t('Deactivate'),
                'desc' => '',
                'apply_button' => [t('Deactivate'),'danger'],
            ];
        }
    }

    public function access(){
        $access = TRUE;
        if (role_access('admin')){
            if ($this->multi_type == 'bulk'){
                $bet_type_ids = $this->bet_type_ids;

                foreach($bet_type_ids as $bet_type_id){
                    $bet_type = $this->bt->get_by_id($bet_type_id, $this->lang);
                    if (!$bet_type) $access = FALSE;
                }
            }else{
                if (!$this->bet_type) $access = FALSE;
            }

        }else{
            $access = FALSE;
        }

        return $access;
    }

    public function apply(){
        $lui = new \BetUi\BetType();

        $bet_type_ids = $this->bet_type_ids;

        foreach ($bet_type_ids as $bet_type_id){
            $this->bt->deactivate($bet_type_id);

            $bet_type = $this->bt->get_by_id($bet_type_id, $this->lang);
            
            \JS::live('replace_with', [
                'element' => '#bet-type-'.$bet_type_id,
                'new_html' => $lui->render_bet_type($bet_type, $this->lang),
            ]);

        }

        if ($this->multi_type == 'bulk'){

            \JS::live('message', [
                'type' => 'danger',
                'message' => t('Bet_types_deactivated'),
            ]);

        }else{

            \JS::live('message', [
                'type' => 'danger',
                'message' => t('Bet_type_deactivated'),
            ]);

        }

        \JS::live('close_popup');

    }

}