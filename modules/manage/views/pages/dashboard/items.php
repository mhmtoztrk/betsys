<?php
/** @var TYPE_NAME $view_type */
/** @var TYPE_NAME $user */
/** @var TYPE_NAME $dashboard_items */

$i_output ='<div class="p-3">';
    $i_output .= '<div class="row">';

    foreach ($dashboard_items as $key =>  $item){
        $i_output .= '<div class="col-sm-6">';

            $i_output .= '<a id="dashboard-card-'.$key.'" class="dashboard-card" href="'.$item['path'].'"><div class="count-card">';

                $i_output .= '<div class="card-left">';
                    $i_output .= '<div class="count-card-icon">'.$item['icon'].'</div>';
                $i_output .= '</div>';

                $i_output .= '<div class="card-right">';
                        $i_output .= '<div class="count-card-total">'.$item['count'].'</div>';
                        $i_output .= '<div class="count-card-title">'.$item['title'].'</div>';
                $i_output .= '</div>';

            $i_output .= '</div></a>';
        $i_output .= '</div>';
    }

    $i_output .='</div>';
$i_output .='</div>';

return $i_output;