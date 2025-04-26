<?php

namespace Response;

class Bet
{

    public function leagues(){
        set_page_title(t('All_Leagues'));
        set_content('leagues/all');
    }

    public function active_leagues(){
        set_page_title(t('Active_Leagues'));
        set_content('leagues/active');
    }

    public function bet_types(){
        set_page_title(t('Bet_Types'));
        set_content('bet_types/list');
    }

    public function edit($bet_type_id){
        $bt = new \BetType();
        $bet_type = $bt->get_by_id($bet_type_id);

        set_page_title(t('Edit').' - '.$bet_type['api_key']);
        
        set_content('bet_types/edit', [
            'bet_type' => $bet_type,
        ]);
    }

    public function bet_action($action){
        set_layout('json');
    
        $callback['status'] = 0;
        $data = $_POST ?? [];
    
        global $user;
        if ($user['uid'] > 0 && $user['role'] == 'standard') {
    
            $callback['status'] = 0;
            $uid = $user['uid'];

            $bs = new \BetSlip();
            if ($user['open_bet_slip']) {
                $slip_id = $user['open_bet_slip'];
            }else{
                $slip_id = $bs->create_open_slip($user['uid']);
            }

            $slip = $bs->load($slip_id);

            $version_check = $slip['version'] == $data['version'];

            $slip_reload = FALSE;
            $slip_reload_required = FALSE;

            $alert_message = FALSE;
            $slip_message = FALSE;

            $bet_slip_ui = new \BetUi\BetSlip([
                'lang' => $data['lang'],
                'view_type' => 'front',
            ]);
    
            switch ($action) {
    
                case 'update_all':
                    

                    break;
    
                case 'submit_slip':
                    
                    if($version_check){

                        $submit = $bs->submit_slip($slip);

                        if (!$submit['status']) {

                            if ($submit['error'] == 'max_winnings_exceeded') {

                                $slip_message = [
                                    'type' => 'error',
                                    'value' => $bet_slip_ui->ui_texts()['max_winnings_message'],
                                ];

                            }elseif ($submit['error'] == 'balance_exceeded') {

                                $slip_message = [
                                    'type' => 'error',
                                    'value' => $bet_slip_ui->ui_texts()['balance_exceeded_message'],
                                ];

                            }

                        }

                        $slip_reload = TRUE;

                    }else{

                        $slip_reload_required = TRUE;

                    }
                    
                    break;
    
                case 'reset_slip':

                    $slip_id = $bs->reset_slip($slip);

                    $slip_reload = TRUE;

                    break;
    
                case 'create_bet':
                    
                    if($version_check){

                        $bs->create_bet($slip_id, [
                            'lang' => $data['lang'],
                            'match_id' => $data['match_id'],
                            'bet_type_id' => $data['bet_type_id'],
                            'bet_value' => $data['bet_value'],
                            'odd_value' => $data['odd_value'],
                        ]);

                        $slip_reload = TRUE;

                    }else{

                        $slip_reload_required = TRUE;

                    }

                    break;
    
                case 'remove_bet':

                    if($version_check){

                        $bs->remove_bet($slip_id, $data['match_id'], $data['bet_type_id']);

                        $slip_reload = TRUE;

                    }else{

                        $slip_reload_required = TRUE;

                    }

                    break;
    
                case 'update_stake':

                    if($version_check){

                        $bs->update_stake($slip, $data['stake']);

                        $slip_reload = TRUE;

                    }else{

                        $slip_reload_required = TRUE;

                    }

                    break;
    
                case 'load_updated_slip':

                    $slip_reload = TRUE;

                    break;
    
                default:

                    $callback['status'] = 0;

                    break;
            }

            if($slip_reload){
                $callback['status'] = 1;

                $slip = $bs->load($slip_id);

                $callback['actions']['slip_html'] = $bet_slip_ui->render_content($slip);
            }

            if($slip_reload_required){
                $callback['status'] = 1;

                $callback['actions']['slip_html'] = '
                    <div class="slip-reload-required">
                        <div class="srr-warning">'.$bet_slip_ui->ui_texts()['srr_warning'].'</div>
                        <button class="load-updated-slip">'.$bet_slip_ui->ui_texts()['srr_button'].'</button>
                    </div>
                ';
            }

            if ($alert_message)$callback['actions']['alert_message'] = $alert_message;
            if ($slip_message)$callback['actions']['slip_message'] = $slip_message;
        }
    
        echo json_encode($callback);
    }    

    public function bet_test(){
        set_layout('json');

        set_content('bet_test');
    }

}