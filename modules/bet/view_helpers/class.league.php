<?php

namespace BetUi;

class League {

    private $token;

    public function __construct() {
        $t = new \Token();
        $this->token = $t->create();
    }

    /**
     * Render the league list, grouped by country
     * @param array $countries - Countries array including leagues
     * @return string - Rendered HTML
     */
    public function render_league_list($countries) {
        $output = '<div class="league-list-container">';

        $groups = '';
        foreach ($countries as $country) {
            if (!empty($country['leagues'])) {
                $groups .= $this->render_country_group($country);
            }
        }

        if($groups != ''){
            $output .= $groups;
        }else{
            $output .= 'There is not any active league';
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Render the country group with its leagues
     * @param array $country - Country data
     * @return string - Rendered HTML
     */
    public function render_country_group($country) {
        $flag = !empty($country['flag']) ? img_by_fid($country['flag']) : null;
    
        // Check if the image path is valid
        if (!$flag) {
            $flag = '/path/to/default/flag.png'; // Default flag image
        }
    
        $output = '<div class="country-group">';
        $output .= '<div class="country-header">';
        $output .= '<img src="/' . $flag . '" alt="' . htmlspecialchars($country['name']) . '" class="country-flag">';
        $output .= '<span class="country-name">' . htmlspecialchars($country['name']) . '</span>';
        $output .= '</div>';
        
        if (!empty($country['leagues'])) {
            foreach ($country['leagues'] as $league) {
                $output .= $this->render_league($league);
            }
        }
    
        $output .= '</div>';
        return $output;
    }

    /**
     * Render a single league with logo and toggle button
     * @param array $league - League data
     * @return string - Rendered HTML
     */
    public function render_league($league) {
        $logo = !empty($league['logo']) ? img_by_fid($league['logo'], 'league_thumb') : null;
    
        // Check if the image path is valid
        if (!$logo) {
            $logo = '/path/to/default/league.png'; // Default league logo
        }

        $button['pars'] = [
            'league_id' => $league['league_id'],
        ];
        $button['class'][] = 'league-status-button';
        $button['token'] = $this->token;

        if($league['status'] === 'active'){

            $button['class'][] = 'league-deactivate-button';
            $button['title'] = 'Deactivate';
            $button['value'] = 'Deactivate';
            $button['action'] = 'league_deactivate';

        }else{

            $button['class'][] = 'league-activate-button';
            $button['title'] = 'Activate';
            $button['value'] = 'Activate';
            $button['action'] = 'league_activate';

        }

        $attrs = [
            'id' => 'league-' . $league['league_id'],
            'class' => [
                'league-item',
                'league-' . $league['status'],
            ]
        ];
    
        $output = '<div'.attrs_output($attrs).'>';
            $output .= '<div class="league-item-left">';
                $output .= '<img src="/' . $logo . '" alt="' . htmlspecialchars($league['name']) . '" class="league-logo">';
                $output .= '<span class="league-name">' . htmlspecialchars($league['name']) . '</span>';
            $output .= '</div>';
            $output .= '<div class="status-toggle" data-league-id="' . $league['league_id'] . '">' . action_element($button) . '</div>';
        $output .= '</div>';
        return $output;
    }
    
}

?>
