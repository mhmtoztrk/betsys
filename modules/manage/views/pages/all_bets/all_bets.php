<?php

/** @var TYPE_NAME $user */
/** @var TYPE_NAME $m_path */
/** @var TYPE_NAME $manage_path */

if(isset($m_path[0])){

    $p0 = $m_path[0];
    if($p0 == 'create'){
        
        $show = 'create';
        set_page_title(t('Create_Bet'));

    }else{

        $p = new Bet();
        $bet = $p->load($p0);

        // $manage_path .= '/'.$bet['bid'];

        \Breadcrumb::set(PANEL_PATH.'/user/'.$user['uid'].'/my-bets', t('Bets'));
        \Breadcrumb::set_group('manage_bet', $bet);

        if($bet){
            if(isset($m_path[1])){
                if($m_path[1] == 'edit'){

                    $show = 'edit';
                    set_page_title(t('Edit_Bet'));

                }

            }
        }
    }
}else{

    $show = 'bets';

    set_page_title(t('Bets'));

}

if($show == 'bets'){

    // return require 'list.php';
    return 'bets';

}elseif($show == 'create' || $show == 'edit'){

    return require 'form.php';

}else{

    return '';

}

?>