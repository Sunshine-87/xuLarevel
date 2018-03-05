<?php

namespace App\Http\Controllers\Dota;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\Controller;
use App\Model\Dota\Matches;
use App\Model\Dota\MatchPlayers;
use App\Model\Dota\MatchPlayerAbilityUps;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;

class CrawlMatchesController extends Controller
{

    public function __construct() {
        $this->query['key'] = env('DOTA2_KEY');
        $this->client = new Client([
            'base_uri' => config('dota.apiDomain'),
            'timeout' => 200,
            'verify' => false,
        ]);
        $this->turn = 50;
        $cacheSeqNum = Cache::has('dota_SeqNum') ? Cache::get('dota_SeqNum') : 0;
        $dbSeqNum = Matches::orderBy('match_seq_num', 'desc')->value('match_seq_num');
        $seqNum = ($cacheSeqNum > $dbSeqNum) ? $cacheSeqNum : $dbSeqNum;
        $this->seqNum = $seqNum ? $seqNum+1 : 1;
        Cache::put('dota_SeqNum', $this->seqNum+($this->turn*100), Carbon::Now()->addMinutes(10));
        $this->jumpOut = 0;
        $this->has_crawl = 0;
    }

    public function crawlMatches() {
        $start_time = Carbon::Now();
        for ($i = 0; $i < $this->turn; $i++) {
            $matches = $this->getMatch();
            foreach ($matches as $key=>$match) {
                $exist = Matches::where('match_id', $match['match_id'])->first();
                if (!$exist) {
                    $match_id = $match_insert['match_id'] = $match['match_id'];
                    $match_insert['radiant_win'] = $match['radiant_win'] ? 1 : 0;
                    $match_insert['pre_game_duration'] = $match['pre_game_duration'];
                    $match_insert['start_at'] = date('Y-m-d H:i:s', $match['start_time']);
                    $match_insert['match_seq_num'] = $match['match_seq_num'];
                    $match_insert['tower_status_radiant'] = $match['tower_status_radiant'];
                    $match_insert['tower_status_dire'] = $match['tower_status_dire'];
                    $match_insert['barracks_status_dire'] = $match['barracks_status_dire'];
                    $match_insert['cluster'] = $match['cluster'];
                    $match_insert['first_blood_time'] = $match['first_blood_time'];
                    $match_insert['lobby_type'] = $match['lobby_type'];
                    $match_insert['human_players'] = $match['human_players'];
                    $match_insert['leagueid'] = $match['leagueid'];
                    $match_insert['positive_votes'] = $match['positive_votes'];
                    $match_insert['negative_votes'] = $match['negative_votes'];
                    $match_insert['game_mode'] = $match['game_mode'];
                    $match_insert['engine'] = $match['engine'];
                    $match_insert['radiant_score'] = $match['radiant_score'];
                    $match_insert['dire_score'] = $match['dire_score'];
                    Matches::create($match_insert);
                    $this->has_crawl++;
                    echo $this->has_crawl.':'.$match['match_seq_num'].PHP_EOL;
                    foreach ($match['players'] as $player) {
                        $player_insert['match_id'] = $match_id;
                        $player_insert['account_id'] = $player_id = isset($player['account_id']) ? $player['account_id'] : 0;
                        $player_insert['player_slot'] = $player['player_slot'];
                        $player_insert['hero_id'] = $player['hero_id'];
                        $player_insert['item_0'] = $player['item_0'];
                        $player_insert['item_1'] = $player['item_1'];
                        $player_insert['item_2'] = $player['item_2'];
                        $player_insert['item_3'] = $player['item_3'];
                        $player_insert['item_4'] = $player['item_4'];
                        $player_insert['item_5'] = $player['item_5'];
                        $player_insert['backpack_0'] = $player['backpack_0'];
                        $player_insert['backpack_1'] = $player['backpack_1'];
                        $player_insert['backpack_2'] = $player['backpack_2'];
                        $player_insert['kills'] = $player['kills'];
                        $player_insert['deaths'] = $player['deaths'];
                        $player_insert['assists'] = $player['assists'];
                        $player_insert['leaver_status'] = isset($player['leaver_status']) ? $player['leaver_status'] : 0;
                        $player_insert['last_hits'] = $player['last_hits'];
                        $player_insert['denies'] = $player['denies'];
                        $player_insert['gold_per_min'] = $player['gold_per_min'];
                        $player_insert['xp_per_min'] = $player['xp_per_min'];
                        $player_insert['level'] = $player['level'];
                        $player_insert['hero_damage'] = isset($player['hero_damage']) ? $player['hero_damage'] : 0;
                        $player_insert['tower_damage'] = isset($player['tower_damage']) ? $player['tower_damage'] : 0;
                        $player_insert['hero_healing'] = isset($player['hero_healing']) ? $player['hero_healing'] : 0;
                        $player_insert['gold'] = isset($player['gold']) ? $player['gold'] : 0;
                        $player_insert['gold_spent'] = isset($player['gold_spent']) ? $player['gold_spent'] : 0;
                        $player_insert['scaled_hero_damage'] = isset($player['scaled_hero_damage']) ? $player['scaled_hero_damage'] : 0;
                        $player_insert['scaled_tower_damage'] = isset($player['scaled_tower_damage']) ? $player['scaled_tower_damage'] : 0;
                        $player_insert['scaled_hero_healing'] = isset($player['scaled_hero_healing']) ? $player['scaled_hero_healing'] : 0;
                        MatchPlayers::create($player_insert);
                        if (isset ($player['ability_upgrades'])) {
                            foreach ($player['ability_upgrades'] as $abilityUp) {
                                $abUp_insert['match_id'] = $match_id;
                                $abUp_insert['player_id'] = $player_id;
                                $abUp_insert['ability'] = $abilityUp['ability'];
                                $abUp_insert['time'] = $abilityUp['time'];
                                $abUp_insert['level'] = $abilityUp['level'];
                                MatchPlayerAbilityUps::create($abUp_insert);
                            }
                        }
                    }
                } else {
                    exit('Crawl '.$this->has_crawl.' matches,cost:'.$start_time->diffInSeconds().' seconds');
                }
            }
        }
        echo 'Crawl '.$this->has_crawl.' matches,cost:'.$start_time->diffInSeconds().' seconds';
        return Response::json(array('status' => true, 'msg' => '抓取完成'));
    }

    public function getMatch() {
        $q = $this->query;
        $q['start_at_match_seq_num'] = $this->seqNum;
        Cache::put('dota_SeqNum', $this->seqNum+($this->turn*100), Carbon::Now()->addMinutes(10));
        $q['matches_requested'] = 100;
        $query = http_build_query($q);
        $matches = json_decode($this->client->get(config('dota.apiUrl.GetMatchHistoryBySequenceNum').'?'.$query)->getBody()->getContents(),true)['result']['matches'];

        $this->seqNum = $matches[count($matches)-1]['match_seq_num']+1;
        return $matches;
    }

}
