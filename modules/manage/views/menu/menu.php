<?php

/** @var TYPE_NAME $manage_menus */
/** @var TYPE_NAME $active_page */
/** @var TYPE_NAME $view_type */
/** @var TYPE_NAME $lang */


$output = '<div class="manage-menu-container">';

    foreach ($manage_menus as $group => $datas){
        $output .= '<div class="manage-menu-group">';
            if(isset($datas['title'][$view_type])) $output .= '<h3>'.$datas['title'][$view_type].'</h3>';
            $output .= '<div class="manage-menu-group-items">';

                foreach ($datas['items'] as $page_key => $page){
                    
                    $item_attrs = $page['attrs'] ?? [];
                    $item_attrs['class'][] = 'manage-menu-item';
                    if($page_key == $active_page) $item_attrs['class'][] = 'active';

                    $path = str_replace('_', '-', $page['path']);
                    $output .= '<div'.attrs_output($item_attrs).'><a href="'.BASE_PATH.$path.'">'.$page['menu_label'].'</a></div>';

                }

            $output .= '</div>';
        $output .= '</div>';
    }

$output .= '</div>';

return $output;

?>