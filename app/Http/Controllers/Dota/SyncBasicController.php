<?php

namespace App\Http\Controllers\Dota;
use Illuminate\Support\Facades\Response;
use GuzzleHttp\Client;
use App\Http\Controllers\Controller;
use App\Model\Dota\Heros;
use App\Model\Dota\Items;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SyncBasicController extends Controller
{
    private $query = array();
    private $client;
    private $changeNum = 0;
    private $deleteNum = 0;
    private $insertNum = 0;

    public function __construct() {
        $this->query['key'] = env('DOTA2_KEY');
        $this->client = new Client([
            'base_uri' => config('dota.apiDomain'),
            'timeout' => 200,
            'verify' => false,
        ]);
    }

    public function syncBasic() {
        $this->syncHeros();
        $this->syncItems();
        $msg = '同步成功，新增记录：'.$this->insertNum.'条，变更记录：'.$this->changeNum.'条，删除记录：'.$this->deleteNum.'条';
        $status = true;
        return Response::json(compact('status', 'msg'), 200);
    }

    public function syncHeros() {
        $q = $this->query;
        $q['language'] = 'EN';
        $query = http_build_query($q);
        $result = json_decode($this->client->get(config('dota.apiUrl.GetHeroes').'?'.$query)->getBody()->getContents(), true);
        $source_id_list = array();
        foreach ($result['result']['heroes'] as $hero) {
            $hero_array = array(
                'source_id' => $hero['id'],
                'name' => $hero['name'],
                'en_name' => $hero['localized_name']
            );
            if ($now_I_have = Heros::where('source_id', $hero['id'])->first()) {
                if ($now_I_have['name'] != $hero['name'] || $now_I_have['en_name'] != $hero['localized_name']) {
                    $this->changeNum++;
                    $now_I_have->update($hero_array);
                }
            } else {
                $this->insertNum++;
                Heros::create($hero_array);
            }
            $source_id_list[] = $hero['id'];
        }
        $should_be_delete = Heros::whereNotIn('source_id', $source_id_list)->get();
        if (count($should_be_delete)) {
            foreach ($should_be_delete as $delete) {
                $this->deleteNum++;
                $delete->delete();
            }
        }
    }

    public function syncItems() {
        $q = $this->query;
        $q['language'] = 'EN';
        $query = http_build_query($q);
        $result = json_decode($this->client->get(config('dota.apiUrl.GetGameItems').'?'.$query)->getBody()->getContents(), true);

        $source_id_list = array();
        foreach ($result['result']['items'] as $item) {
            $item_array = array(
                'source_id' => $item['id'],
                'name' => $item['name'],
                'en_name' => $item['localized_name'],
                'cost' => $item['cost'],
                'secret_shop' => $item['secret_shop'],
                'side_shop' => $item['side_shop'],
                'recipe' => $item['recipe']
            );
            if ($now_I_have = Items::where('source_id', $item['id'])->first()) {
                if ($now_I_have['name'] != $item['name'] ||
                    $now_I_have['en_name'] != $item['localized_name'] ||
                    $now_I_have['cost'] != $item['cost'] ||
                    $now_I_have['secret_shop'] != $item['secret_shop'] ||
                    $now_I_have['side_shop'] != $item['side_shop'] ||
                    $now_I_have['recipe'] != $item['recipe']
                )
                {
                    $this->changeNum++;
                    $now_I_have->update($item_array);
                }
            } else {
                $this->insertNum++;
                Items::create($item_array);
            }
            $source_id_list[] = $item['id'];
        }
        $should_be_delete = Items::whereNotIn('source_id', $source_id_list)->get();
        if (count($should_be_delete)) {
            foreach ($should_be_delete as $delete) {
                $this->deleteNum++;
                $delete->delete();
            }
        }
    }
}
