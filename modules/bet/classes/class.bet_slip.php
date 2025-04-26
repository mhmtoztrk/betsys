<?php

namespace BetUi;

class BetSlip {

    private $lang;
    private $view_type;

    public function __construct($pars = []) {
        $this->lang = $pars['lang'] ?? current_lang();
        $this->view_type = $pars['view_type'] ?? 'front';
    }

    /**
     * Render slip content
     *
     * @param array $slip
     * @return string
     */
    public function render_content($slip) {

        $output = '<div class="page-deactive"></div>';

            $output .= '<div class="slip-overlay"><div class="loading-icon">'.LOADING_ICON.'</div></div>';

            if (!$slip) {
                return '<div class="bet-slip empty">'.t('No_Bet_Slip').'</div>';
            }

            $bet_count = (int)($slip['bets_count'] ?? 0);

            $texts = $this->ui_texts();

            $attrs = [
                'class' => ['bet-slip', 'bet-slip-'.$slip['status']],
                'data-texts' => htmlspecialchars(json_encode($texts), ENT_QUOTES, 'UTF-8'),
                'data-version' => $slip['version'] ?? 1,
            ];
            $reset_attrs = [
                'class' => ['reset-slip-button']
            ];

            if($slip['status'] == 'submitted'){
                $attrs['class'][] = 'bet-slip-disabled';
                $reset_attrs['class'][] = 'disabled';
            }

            $output .= '<div ' . attrs_output($attrs) . '>';
            
            $output .= $this->render_header($slip);

            $output .= '<div class="bet-slip-body">';

                if ($bet_count > 0) {

                    $output .= '<div class="bet-slip-reset"><div'.attrs_output($reset_attrs).'>'.CLEAR_ICON.$texts['clear_all'].'</div></div>';
                }
                $output .= $this->render_matches($slip);

            $output .= '</div>';

            if ($bet_count > 0) $output .= $this->render_footer($slip);

            $output .= '</div>';

        $output .= '</div>';

        return $output;
    }

    public function render_header($slip) {
        $texts = $this->ui_texts();
    
        $total_odds = floor($slip['total_odds'] * 100) / 100;
    
        $attrs = [
            'class' => ['bet-slip-header'],
            'data-toggle' => 'bet-slip-body',
        ];

        $bet_count = (int)($slip['bets_count'] ?? 0);

        if (empty($slip['bets'])){
            $title = $texts['bet_slip'];
        }else{

            $title = '<span class="bet-count">' . $bet_count . ' ';
            $title .= $bet_count > 1 ? $texts['bets'] : $texts['bet'];
            $title .= '</span>';

        }
    
        $output = '<div ' . attrs_output($attrs) . '>';
            $output .= '<div class="slip-title">' . $title . '</div>';
            $output .= '<div class="slip-header-right">';

                if ($bet_count > 0) {

                    $output .= '<div class="slip-total-odds"><lb>' . $texts['total_odds'] . ': </lb><vl>' . $total_odds . '</vl></div>';

                }

            $output .= '</div>';
        $output .= '</div>';
    
        return $output;
    }
    
    public function render_matches($slip) {
        $bets = $slip['bets'] ?? [];
    
        if (empty($bets)) {
            return '<div class="no-bets">' . $this->ui_texts()['no_bets_selected'] . '</div>';
        }
    
        $output = '';
    
        foreach ($bets as $match_id => $match) {
            $output .= $this->render_match($match_id, $match);
        }
    
        return $output;
    }
    
    public function render_match($match_id, $match) {
        $match_name = htmlspecialchars($match['name'] ?? '');
        $bets = $match['bets'] ?? [];
    
        $output = '<div class="match-block" data-match_id="' . (int)$match_id . '">';
    
        // Match Header (match name)
        $output .= '<div class="match-header">';
            $output .= '<span class="match-name">' . $match_name . '</span>';
            $output .= '<span class="match-bets-count"> (' . count($bets) . ')</span>';
        $output .= '</div>';
    
        // Match Bets
        $output .= '<div class="match-bets">';
        foreach ($bets as $bet_type_id => $bet) {
            $output .= $this->render_bet($match_id, $bet_type_id, $bet);
        }
        $output .= '</div>';
    
        $output .= '</div>';
    
        return $output;
    }

    public function render_bet($match_id, $bet_type_id, $bet) {
        $ui_texts = $this->ui_texts($this->lang);

        $label = htmlspecialchars($bet['name'] ?? '');
        $value = htmlspecialchars($bet['bet_value'] ?? '');
        $odd = number_format((float)($bet['odd_value'] ?? 0), 2);
        $status = $bet['status'] ?? 'active';
    
        $classes = ['slip-bet'];
        if ($status !== 'active') {
            $classes[] = 'bet-inactive';
        }
    
        $attrs = [
            'class' => $classes,
            'data-match_id' => $match_id,
            'data-bet_type_id' => $bet_type_id,
            'data-bet_value' => $value,
        ];
    
        $output = '<div ' . attrs_output($attrs) . '>';
            $output .= '<div class="bet-info">';
                $output .= '<div class="bet-label">' . $label . '</div>';
                $output .= '<div class="bet-info-bet">';
                    $output .= '<div class="bet-value">' . $value . '</div>';
                    $output .= '<div class="bet-odd">' . $odd . '</div>';
                $output .= '</div>';
            $output .= '</div>';
    
            $remove_attrs = [
                'title' => $ui_texts['remove_title'],
                'class' => ['bet-remove'],
            ];
            if($slip['status'] == 'submitted'){
                $remove_attrs['class'][] = 'disabled';
            }
            // Remove button
            $output .= '<div'.attrs_output($remove_attrs).'>×</div>';
        $output .= '</div>';
    
        return $output;
    }

    /**
     * Render the bet slip footer (stake input, total odds, payout, submit button)
     *
     * @param array $slip
     * @return string
     */
    private function render_footer($slip) {
        $stake = $slip['total_stake'] ?? 0;
        $total_odds = $slip['total_odds'] ?? 1;
        $potential_payout = $slip['potential_payout'] ?? 0;

        $ui_texts = $this->ui_texts($this->lang);

        $output = '<div class="bet-slip-footer">';

            $output .= '<div class="slip-message"></div>';

            $output .= '<div class="row">';

                // Stake input
                $stake_attrs = [
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0',
                    'name' => 'stake',
                    'value' => htmlspecialchars($stake),
                    'class' => ['stake-input', 'form-control'],
                ];
                if($slip['status'] == 'submitted'){
                    $stake_attrs['class'][] = 'disabled';
                }

                $output .= '<div class="bet-slip-stake bs-footer-col col-6 col-sm-4">';
                    $output .= '<label>' . htmlspecialchars($ui_texts['stake']) . ' (€)</label>';
                    $output .= '<div class="bss-footer-val"><input'.attrs_output($stake_attrs).' /></div>';
                $output .= '</div>';

                // Total odds
                // $output .= '<div class="bet-slip-odds">';
                //     $output .= '<span>' . htmlspecialchars($ui_texts['total_odds']) . ':</span> ';
                //     $output .= '<b>' . number_format($total_odds, 2) . '</b>';
                // $output .= '</div>';

                // Potential payout
                $output .= '<div class="bet-slip-payout bs-footer-col col-6 col-sm-4">';

                    $output .= '<div class="slip-payout-value">';
                        $output .= '<label>' . htmlspecialchars($ui_texts['potential_payout']) . '</label>';
                        $display_payout = floor($potential_payout * 100) / 100;
                        $output .= '<div class="bss-footer-val"><b>' . $display_payout . ' €</b></div>';
                    $output .= '</div> ';

                    $output .= '<div class="slip-payout-loading">' . LOADING2_ICON . '</b></div> ';
                $output .= '</div>';

                // Confirm button
                $output .= '<div class="deposit-slip-submit bs-footer-col col-12 col-sm-4">';
                    if ($slip['status'] == 'submitted') {
                        $output .= '<div class="slip-delay-warning">'.$ui_texts['slip_delay_warning'].'</div>';
                        $output .= '<div class="slip-delay-title">'.$ui_texts['slip_delay_title'].'</div>';
                        $output .= '<div class="slip-delay-description">'.$ui_texts['slip_delay_desc'].'</div>';
                    }else{
                        $output .= '<button type="button" class="deposit-slip-button">' . htmlspecialchars($ui_texts['deposit_bet']) . '</button>';
                    }
                $output .= '</div>';


            $output .= '</div>';

        $output .= '</div>';

        return $output;
    }

    public function ui_texts() {
        return [
            'bet_slip' => 'Bet Slip',
            'stake' => 'Stake',
            'total_odds' => 'Total Odds',
            'potential_payout' => 'Potential Payout',
            'deposit_bet' => 'Deposit Bet',
            'slip_adding' => 'Slip Adding',
            'clear_all' => 'Clear All',
            'no_bets_selected' => 'No Bets Selected',
            'something_went_wrong' => 'Something went wrong',
            'odd_not_active' => 'This odd is not active to bet now',
            'max_winning_exceeded' => 'Maximum winning limit exceeded',
            'submit_wait' => 'Submitting your bet, please wait...',
            'slip_updated' => 'Bet slip has been updated',
            'slip_expired' => 'Bet slip expired, please reselect your bets',
            'bet' => 'Bet',
            'bets' => 'Bets',
            'stake_zero_warning' => 'Stake should be bigger than 0',
            'balance_exceeded_message' => 'Your balance not enough to deposit',
            'slip_delay_warning' => 'Please Wait',
            'slip_delay_title' => 'Your slip will be saved in <b>'.SLIP_DELAY.'</b> seconds',
            'slip_delay_desc' => 'This is for security against live odds changes',
            'remove_title' => 'Remove',
        ];
    }

}

?>