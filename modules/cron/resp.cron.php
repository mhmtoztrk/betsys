<?php

namespace Response;

class Cron
{

    public function cr(){
        set_layout('json');

        if(isset($_GET['token']) && $_GET['token'] == 'nfM6IAOlT63'){

            $json_file = CUSTOM_MODULES.'/cron/crons.json';
            $json = file_get_contents($json_file);
        
            $crons = json_decode(file_get_contents($json_file), 1);

            $cr = new \Cron();
            foreach ($crons as $cron_key => $data) {
                if ($data['last_called'] + $data['period'] <= time()) {
                    if (method_exists($cr, $cron_key)) {
                        $cr->$cron_key();
                        $crons[$cron_key]['last_called'] = time();
                    }
                }
            }

            $df = new \Df();
            $df->set(json_encode($crons, JSON_PRETTY_PRINT), $json_file);

        }

    }

}