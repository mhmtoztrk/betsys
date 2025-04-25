<?php

namespace BetUi;

class BetType {

    private $token;

    public function __construct() {
        $t = new \Token();
        $this->token = $t->create();
    }

    /**
     * Render the bet_type list, grouped by country
     * @param array $bet_types - Bet types array 
     * @return string - Rendered HTML
     */
    public function render_bet_type_list($bet_types) {
        $output = '<div class="bet-type-list">';

        if(count($bet_types) > 0){

            foreach ($bet_types as $bet_type) {
                $output .= $this->render_bet_type($bet_type);
            }

        }else{

            $output .= 'There is not any active bet type';

        }

        $output .= '</div>';
        return $output;
    }
    

    /**
     * Render a single bet_type
     * @param array $bet_type - Bet type data
     * @return string - Rendered HTML
     */
    public function render_bet_type($bet_type, $lang = null) {
        $lang = $lang ?? current_lang();
        $button['pars'] = [
            'bet_type_id' => $bet_type['bet_type_id'],
            'lang' => $lang,
        ];
        $button['class'][] = 'bet-type-status-button';
        $button['token'] = $this->token;

        if($bet_type['status'] === 'active'){

            $button['class'][] = 'bet-type-deactivate-button';
            $button['title'] = 'Deactivate';
            $button['value'] = 'Deactivate';
            $button['action'] = 'bet_type_deactivate';

        }else{

            $button['class'][] = 'bet-type-activate-button';
            $button['title'] = 'Activate';
            $button['value'] = 'Activate';
            $button['action'] = 'bet_type_activate';

        }

        $attrs = [
            'id' => 'bet-type-' . $bet_type['bet_type_id'],
            'class' => [
                'bet-type-item',
                'bet-type-' . $bet_type['status'],
            ]
        ];
    
        $output = '<div'.attrs_output($attrs).'>';
            $output .= '<div class="bet-type-item-left">';
                $output .= '<div class="bet-type-api-key">' . htmlspecialchars($bet_type['api_key']) . '</div>';
                $output .= '<div class="bet-type-name"><b>Name('.$lang.'):</b> ' . htmlspecialchars($bet_type['texts']['name'] ?? '') . '</div>';
                $output .= '<div class="bet-type-desc"><b>Description('.$lang.'):</b> ' . htmlspecialchars($bet_type['texts']['description'] ?? '') . '</div>';
            $output .= '</div>';
            $output .= '<div class="bet-type-item-right">';
                $output .= '<div class="bet-type-edit"><a href="'.PANEL_URL.'/bet-type/'.$bet_type['bet_type_id'].'/edit" class="btn">Edit</a></div>';
                $output .= '<div class="status-toggle" data-bet-type-id="' . $bet_type['bet_type_id'] . '">' . action_element($button) . '</div>';
            $output .= '</div>';
        $output .= '</div>';
        return $output;
    }

    /**
     * Render the edit form for a specific bet type.
     *
     * This method generates a form to edit the name and descriptions of a given bet type.
     * The form will include fields for editing the bet type name and language-specific descriptions.
     *
     * @return string The generated HTML form for editing the bet type.
     */

    public function edit_form($bet_type) {
        
        $form = [
            'name' => 'bet_type_form',
            'submit' => 'BetType/bet_type_form_submit',
            'hiddens' => [
                'bet_type_id' => $bet_type['bet_type_id'],
            ],
            'fieldsets' => [
                'main' => [
                    'label' => '',
                ],
            ],
        ];

        $langs = all_langs();
        foreach ($langs as $lang => $lang_data) {
            $form['fieldsets'][$lang] = ['label' => $lang_data['title']];

            $form['fields'][$lang.'_name'] = [
                'label' => t('Name'),
                'type' => 'textfield',
                'values' => $bet_type['texts'][$lang]['name'],
                'required' => TRUE,
                'fieldset' => $lang,
            ];

            $form['fields'][$lang.'_description'] = [
                'label' => t('Description'),
                'type' => 'textfield',
                'values' => $bet_type['texts'][$lang]['description'],
                'required' => FALSE,
                'fieldset' => $lang,
            ];

        }

        $form['fields']['messages'] = [
            'type' => 'markup',
            'value' => '<div class="form-messages"></div>',
        ];

        $form['fields']['submit'] = [
            'type' => 'submit',
            'text' => t('Save'),
            'attributes' => [
                'class' => ['btn']
            ],
        ];

        return form($form);
    }
    
}

?>
