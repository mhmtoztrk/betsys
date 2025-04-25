<?php

// $bs = new BetSlip();
// $bs->update_not_completed_slip(17);
// $bs->confirm_slip(17);

// $mtch = new Matches();
// $matches_today = $mtch->get_grouped_matches([
//     'from_day' => 0,
//     'to_day' => 0
// ]);
// pr($matches_today);
// $ba = new BetApi();
// $m = new Matches();

// $stats = $ba->get_match_stats(1238134);
// $m->save_match_stats(1238134, $stats);

/*
$ba = new BetApi();
$stats = $ba->get_match_stats(1238134); // Örnek fixture_id

$m = new Matches();
$m->save_match_stats(1238134, $stats);
*/

// $day = 2;
// $bet_api     = new BetApi();
// $bet_api->sync_upcoming_matches($day);
// $bet_api->sync_odds($day);
// return;

/*
$db = new DB();
$all = $db->from('matches')
->all();

foreach ($all as $item) {
    
    $db->update('matches')
    ->where('match_id',$item['match_id'])
    ->set([
        'match_date' => $item['match_date'] + (3 * 86400),
    ]);

}
*/

// $cr = new Cron();
// $cr->delete_sessions();

/*
$db = new PDO("mysql:host=localhost;dbname=bet", "root", "39951967");
$stmt = $db->prepare("SELECT COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'bet' AND TABLE_NAME = 'bet_slips'");
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($columns, JSON_PRETTY_PRINT);
*/


// pr(img_styles());

// $db = new DB();
// $all = $db->from('matches')
// ->all();

// $m     = new Matches();
// foreach ($all as $item) {

// }

// $logo = img_by_fid($item['logo']);
// echo '<img src="/' . $logo . '"  class="league-logo">';
// $bet_api->sync_odds(2);

/*
$db = new DB();
$m     = new Matches();

$all = $db->from('matches')
->all();

foreach ($all as $item) {
    $m->cache_match($item['match_id']);
}
*/
// $match_id = 1;

// pr($m->load($match_id, 'full'));
// $m->cache_match($match_id);

// $bt = new BetType();
// $bt->update(61, [
//     'api_key' => 'test bet type2',
//     'texts' => [
//         'en' => [
//             'name' => 'en name2',
//             'description' => 'en description2',
//         ],
//         'de' => [
//             'name' => 'de name',
//             'description' => 'de description2',
//         ],
//     ]
// ]);
// pr($bt->get_by_id(3));

/*
$bet_type_map = BetType::get_bet_type_map();
// pr($bet_type_map);

$db = new DB();

$all = $db->from('odds')
->all();
foreach ($all as $item) {
    $bet_type = $item['bet_type'];
    $bet_type_id = $bet_type_map[$bet_type];
    $odds_id = $item['odds_id'];

    $db->update('odds')
    ->where('odds_id',$odds_id)
    ->set([
        'bet_type_id' => $bet_type_id,
    ]);
}
*/
// $db = new DB();
// $bt = new BetType();

// foreach ($list as $en => $de) {
//     $item = $db->from('bet_type_texts')
//     ->where('name',$en)
//     ->where('lang','en')
//     ->first();

//     if($item){

//         $db->update('bet_types')
//         ->where('bet_type_id',$item['bet_type_id'])
//         ->set([
//             'api_key' => $en,
//         ]);

//     }

// }

/*
$b = new Bet();
$bet_types = $b->bet_types();

$output = '['."\n";

foreach ($bet_types as $name => $descs) {
    $output .= "\t".'\''.$name.'\' => \'xxxx\','."\n";
}

$output .= ']';

echo $output;
*/
/*
$db = new DB();

$all = $db->from('odds')
->where('match_id',3)
->all();

$list = [];
foreach ($all as $item) {
    $list[$item['bet_type']][$item['value']] = $item['odd_value'];
}
pr($list);
*/
// $output = '['."\n";

// foreach ($list as $bet_type) {
//     $output .= "\t".'\''.$bet_type.'\' => ['."\n";
//         $output .= "\t\t".'\'en\' => \'xxxx\','."\n";
//         $output .= "\t\t".'\'de\' => \'xxxx\','."\n";
//     $output .= "\t".'],'."\n";
// }

// $output .= ']';

// echo $output;

/*
$bet_api     = new BetApi();
$bet_api->sync_odds();
*/

// echo img_by_fid(29);

// $lui = new \BetUi\League();
// $lg  = new League();
// $league = $lg->get_active_season_leagues();
// pr($league);


/*
$json = file_get_contents('http://localhost/bet/p/ligler.json');
$leagues = json_decode($json, 1)['response'];
// pr($leagues);
echo count($leagues);

$cnt  = new Country();
$lg  = new League();

foreach ($leagues as $league) {

    //country
    $country_id = $cnt->upsert([
        'name' => $league['country']['name'],
        'code' => $league['country']['code'],
        'flag' => $league['country']['flag'],
    ]);

    $season_data = [];
    foreach ($league['seasons'] as $season_item) {
        if($season_item['current']){
            $season_data = [
                'season' => $season_item['year'],
                'start_date' => strtotime($season_item['start']),
                'end_date' => strtotime($season_item['end']),
            ];
        }
    }
    $add = [
        'league_id' => $league['league']['id'],
        'name' => $league['league']['name'],
        'type' => $league['league']['type'],
        'country' => $country_id,
        'logo' => $league['league']['logo'],
        'season' => $season_data['season'],
        'start_date' => $season_data['start_date'],
        'end_date' => $season_data['end_date'],
    ];
    $lg->upsert($add);
    // pr($add);
}
*/



/*
$bet_api     = new BetApi();
$match_model = new Matches();
$odds_model  = new Odds();

$odds_pages = $bet_api->get_odds([
    'league' => 78,
]);

foreach ($odds_pages as $odds_data) {
    $fixture_id = $odds_data['fixture_id'];
    $match = $match_model->get_by_fixture_id($fixture_id);
    if ($match) {
        echo "Saving odds for match_id: {$match['match_id']} (fixture_id: $fixture_id)\n";
        $odds_model->save_odds($match['match_id'], $odds_data);
    } else {
        echo "Match not found for fixture_id: $fixture_id. Skipping...\n";
    }
}
*/
// pr($odds_pages);

/*
$bet_api     = new BetApi();
$match_model = new Matches();
$odds_model  = new Odds();
$league_model = new League();

// Get active leagues with current season info
$leagues = $league_model->get_active_season_leagues();

foreach ($leagues as $league) {
    $league_id = $league['league_id'];
    $season    = $league['season'];

    echo "Checking odds for League ID: $league_id, Season: $season\n";

    $odds_pages = $bet_api->get_odds([
        'league' => $league_id,
        'season' => $season
    ]);

    foreach ($odds_pages as $fixture_id => $odds_data) {
        $match = $match_model->get_by_fixture_id($fixture_id);
        if ($match) {
            echo "Saving odds for match_id: {$match['match_id']} (fixture_id: $fixture_id)\n";
            $odds_model->save_odds($match['match_id'], $odds_data);
        } else {
            echo "Match not found for fixture_id: $fixture_id. Skipping...\n";
        }
    }
}*/

// $matches_model = new Matches();

// $params = [
//     'status' => 'upcoming',
//     'league' => 140,
//     'from' => '2025-03-01',
//     'to' => '2025-05-01',
//     'limit' => 5,
//     'team_id' => 529,
// ];

// $matches = $matches_model->get_matches($params);
// pr($matches);


/*
$bet_api = new BetApi();
$bet_api->sync_upcoming_matches(3);
*/

/*
$bet_api = new BetApi();
$match_model = new Matches();
$odds_model = new Odds();

// Yeni get_matches metodunu kullanıyoruz
$matches = $match_model->get_matches([
    'status' => 'upcoming',
    'limit' => 1
]);

foreach ($matches as $match) {
    $fixture_id = $match['fixture_id'];
    $match_id = $match['match_id'];

    echo "Checking fixture: $fixture_id\n";

    $odds_data = $bet_api->get_odds_by_fixture($fixture_id);

    if (!empty($odds_data)) {
        echo "Saving odds for match_id: $match_id (fixture_id: $fixture_id)\n";

        // (Opsiyonel) Önceki kayıtları sil
        // $odds_model->delete_by_match($match_id);

        pr($odds_data);

        $odds_model->save_odds($match_id, $odds_data);
    } else {
        echo "No odds found for fixture_id: $fixture_id\n";
    }
}
*/


// $bet_api = new BetApi();
// pr($bet_api->get_odds_by_fixture(1212938));
// echo json_encode($bet_api->get_odds_by_fixture(1212938));


// $bet_api = new BetApi();
// echo json_encode($bet_api->get_upcoming_matches(
//     [
//         'days' => 18,
//         'league' => 39,
//         'season' => 2024,
//     ]
// ));
// echo json_encode($bet_api->get_leagues());

// echo date("Y-m-d");

// $f = new File();
// $flag_file = $f->save_image_from_url("https://media.api-sports.io/flags/de.svg");
// $l = new League();
// pr($l->get_active_season_leagues());
// pr($flag_file);
// $popular_leagues = [
//     [
//         'league_id' => 135,
//         'league_name' => 'Serie A',
//         'league_type' => 'League',
//         'league_logo' => 'https://media.api-sports.io/football/leagues/135.png',
//         'country_name' => 'Italy',
//         'country_code' => 'IT',
//         'country_flag' => 'https://media.api-sports.io/flags/it.svg',
//         'season' => 2024,
//         'season_start' => '2024-08-17',
//         'season_end' => '2025-05-25',
//     ],
// ];



// $f = new File();
// $country_model = new Country();
// $league_model = new League();

// foreach ($popular_leagues as $item) {
//     // Ülke bayrağını indir
//     $flag_fid = null;
//     if (!empty($item['country_flag'])) {
//         $flag_file = $f->save_image_from_url($item['country_flag']);
//         $flag_fid = $flag_file['fid'] ?? null;
//     }

//     // Ülkeyi ekle veya varsa ID'sini al
//     $country_id = $country_model->upsert([
//         'name' => $item['country_name'],
//         'code' => $item['country_code'],
//         'flag' => $flag_fid,
//     ]);

//     // Lig logosunu indir
//     $logo_file = $f->save_image_from_url($item['league_logo']);
//     $logo_fid = $logo_file['fid'] ?? null;

//     // Ligi ekle
//     $league_model->upsert([
//         'league_id'   => $item['league_id'],
//         'name'        => $item['league_name'],
//         'type'        => $item['league_type'],
//         'logo'        => $logo_fid,
//         'country'     => $country_id,
//         'season'      => $item['season'],
//         'start_date'  => strtotime($item['season_start']),
//         'end_date'    => strtotime($item['season_end']),
//     ]);
// }


return;