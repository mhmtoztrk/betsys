<?php

/** @var TYPE_NAME $options */

$s_output = '<div class="dashboard-summary">';

    if(isset($options['title'])) $s_output .= '<h4>'.$options['title'].'</h4>';

    $s_output .= '<div class="ds-items">';

    foreach($options['items'] as $key => $item) {

        $s_output .= '<div class="ds-item ds-item-'.$key.'"><div class="ds-label">'.$item['label'].':</div> <div class="ds-val">'.$item['value'].'</div></div>';

    }

    $s_output .= '</div>';

    if(isset($options['link'])) $s_output .= '<div class="ds-link"><a class="btn btn-secondary" href="'.$options['link']['path'].'">'.$options['link']['title'].'</a></div>';

$s_output .= '</div>';

return $s_output;