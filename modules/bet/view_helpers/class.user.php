<?php

namespace BetUi;

class User {

    public function balance_alert($balance){

        return '<div class="alert alert-warning" role="alert">Current balance: <b>'.format_currency($balance).'</b></div>';

    }
}


?>
