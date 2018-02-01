<?php

namespace App\Http\Controllers;

use App\Model\PartnerMember;
use App\Model\GuiderInvitation;
use App\Model\Partner;
use App\Model\GuiderLog;
use App\Model\PartnerOrderDetail;
use App\Model\Logs;
use App\Model\GuiderOrder;
use App\Model\PartnerOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Model\Order;
use App\Model\Guider;
use App\Model\GuiderOrderDetail;
use App\Model\Member;
use App\Model\GuiderSetting;


class OrderInfoController extends Controller
{
    private $guider_change = array();
    private $error = array();
    private $warning = array();

    private function getHmi($second) {


        $h = floor(($second % (3600*24)) / 3600);
        $m = floor((($second % (3600*24)) % 3600) / 60);
        $i = $second-$h*3600-$m*60;

        return $h.':'.$m.':'.$i;
    }

    public function orderInfo($order_sn, Request $request) {
        $params = $request->all();
        $abc = '';
        if (isset($params['xu']) && $params['xu'] == 1) {
            $abc .= 'xuboheng::';
        }
        \Log::info(date('Y-m-d H:i:s').':'.$abc.'orderInfo=>'.$order_sn);

        $order = Order::where('order_sn', $order_sn)->first();
        if (!$order) return Response::json(array('status' => false, 'errmsg' => '没有该订单'), 200);
        $member_name = Member::where('id', $order['member_id'])->value('name');
        $order['member_name'] = $member_name;
        $order_id = $order['id'];
        $guider_order = GuiderOrder::where('order_id', $order_id)->first();
        if ($guider_order) {
            $guider_order = GuiderOrderDetail::where('order_id', $order_id)->get();
            if ((strtotime($guider_order[0]['created_at'])-strtotime($order['created_at']))>600) {
                $this->error[]='生成推客订单过慢，耗时：'.$this->getHmi(strtotime($guider_order[0]['created_at'])-strtotime($order['created_at'])) ;
            }
        }



        $guider_self_buy = $this->getGuiderSelfBuy($order);

        if ($guider_self_buy == 1) {
            $this->getGid($order, $gid);

            $gid = isset($gid) ? $gid : GuiderInvitation::where('member_id', $order['member_id'])
                ->where('type', 1)->where('shop_id', $order['shop_id'])->where('bind_at', '<', $order['created_at'])->value('guider_id');
            var_dump($gid);exit;
        } else {
            $gid = Guider::where('shop_id', $order['shop_id'])->where('member_id', $order['member_id'])
                ->where('status', '!=', 1)->where('created_at', '<', $order['created_at'])->value('id');
            if (!$gid) {
                $this->getGid($order, $gid);
            }

            $gid = $gid ? $gid : GuiderInvitation::where('member_id', $order['member_id'])
                ->where('type', 1)->where('shop_id', $order['shop_id'])->where('bind_at', '<', $order['created_at'])->value('guider_id');
        }
        $pid = $this->getPartnerId($order['member_id'], $order['shop_id'], $order['created_at']);

        if ($gid) {
            $mid = Guider::where('id', $gid)->value('member_id');
            $pid = $pid ? $pid : $this->getPartnerId($mid, $order['shop_id'], $order['created_at']);
            $level_1 = $gid;
            $level_2 = $this->getPid($order, $level_1);
            if($level_2) {
                $level_3 = $this->getPid($order, $level_2);
            }
            for ($i = 0; $i < 3; $i++) {
                if ((!isset($guider_order[$i]) && ${'level_'.($i+1)} != 0) || $guider_order[$i]['guider_id'] != ${'level_'.($i+1)}) {
                    $this->error[] = '推客订单和预期不同';
                }
            }
            $guider_order_reasonable = compact('level_1', 'level_2', 'level_3');
        } else {
            $kf_target = ($guider_self_buy == 1) ? GuiderInvitation::where('member_id', $order['member_id'])
                ->where('type', 1)->where('shop_id', $order['shop_id'])->
                where('bind_at', '>', $order['created_at'])->first()
                :
                Guider::where('shop_id', $order['shop_id'])->where('member_id', $order['member_id'])
                    ->where('status', '!=', 1)->where('created_at', '>', $order['created_at'])->first();
            if ($kf_target) {
                $kf_gid = ($guider_self_buy == 1) ? $kf_target['guider_id'] : $kf_target['id'];
                $kf_createdAt = ($guider_self_buy == 1) ? $kf_target['bind_at'] : $kf_target['created_at'];
                $kf_Account = Guider::where('id', $kf_gid)->value('member_id')+1000002016;
                $this->warning[] = '客服可能会问:为什么'.$kf_Account.'下没有生成佣金。';
                $this->warning[] = '回复:'.($guider_self_buy == 1) ? '推客锁定关系' : '推客关系'.'（'.$kf_createdAt.'）生成在订单（'.$order['created_at'].'）之后';
            }
        }


        $partner_order = PartnerOrderDetail::where('order_id', $order_id)->where('partner_type', 1)->first();
        $partner_order = $partner_order ? $partner_order : array();
        if ($pid) {
            $partner_reasonable = $pid;
            if ((!isset($partner_order['partner_id']) && $pid) || $pid != $partner_order['partner_id']) {
                $this->error[] = '团队合伙人订单和预期不同';
            }
        }
        echo '推客订单'.'<br \/>';
        echo '=============='.'<br \/>';
        if (empty($guider_order) && empty($level_1)) {
            echo '无'.'<br \/>';
        } else {
            if (!empty($guider_order[0]) || (!empty($level_1) && $level_1)) {
                $id = !empty($guider_order[0]) ? $guider_order[0]['guider_id'] : 0;
                echo '一级：'.$id.'预期：'.$guider_order_reasonable['level_1'].'<br \/>';
                if (!empty($guider_order[1]) || (!empty($level_2) && $level_2)) {
                    $id = !empty($guider_order[1]) ? $guider_order[1]['guider_id'] : 0;
                    echo '二级：'.$id.'预期：'.$guider_order_reasonable['level_2'].'<br \/>';
                    if (!empty($guider_order[2]) || (!empty($level_3) && $level_3)) {
                        $id = !empty($guider_order[2]) ? $guider_order[2]['guider_id'] : 0;
                        echo '三级：'.$id.'预期：'.$guider_order_reasonable['level_3'].'<br \/>';
                    }
                }
            }
        }
        echo '<br \/>';

        echo '团队合伙人订单'.'<br \/>';
        echo '=============='.'<br \/>';
        if (empty($partner_order)) {
            echo '无'.'<br \/>';
        } else {
            echo '合伙人ID：'.$partner_order['partner_id'].'预期：'.$partner_reasonable.'<br \/>';
        }
        echo '<br \/>';

        echo '推客关系变化'.'<br \/>';
        echo '=============='.'<br \/>';
        if (empty($this->guider_change)) {
            echo '无'.'<br \/>';
        } else {
            foreach($this->guider_change as $value) {
                echo $value.'<br \/>';
            }
        }
        echo '<br \/>';

        echo '错误'.'<br \/>';
        echo '=============='.'<br \/>';
        if (empty($this->error)) {
            echo '无'.'<br \/>';
        } else {
            foreach($this->error as $value) {
                echo $value.'<br \/>';
            }
        }
        echo '<br \/>';

        echo '预警'.'<br \/>';
        echo '=============='.'<br \/>';
        if (empty($this->warning)) {
            echo '无'.'<br \/>';
        } else {
            foreach($this->warning as $value) {
                echo $value.'<br \/>';
            }
        }
        exit;
        $result =  compact('order', 'partner_order', 'guider_order', 'guider_order_reasonable', 'partner_reasonable');
        $result['errmsg'] = $this->error;
        $result['guider_change'] = $this->guider_change;
        return Response::json($result, 200);
    }

    private function getPartnerId($member_id, $shop_id, $created_at) {
        $pid = Partner::where('member_id', $member_id)->where('created_at', '<', $created_at)->value('id');



        $pid = $pid ? $pid : PartnerMember::where('member_id', $member_id)->where('shop_id', $shop_id)->where('created_at', '<', $created_at)->value('superior_partner');


        if (!$pid && $kf_target = PartnerMember::where('member_id', $member_id)->where('shop_id', $shop_id)->first()) {
            $kf_pid = $kf_target['superior_partner'];
            $kf_createdAt = $kf_target['created_at'];
            $kf_Account = Partner::where('id', $kf_pid)->value('member_id')+1000002016;
            foreach ($this->warning as $value) {
                if (strpos($value, ''.$kf_Account) !== false) {
                    return $pid;
                }
            }
            $this->warning[] = '客服可能会问:为什么'.$kf_Account.'下没有生成分红。';
            $this->warning[] = '回复:合伙人关系（'.$kf_createdAt.'）生成在订单（'.$created_at.'）之后';
        }
        return $pid;
    }


    private function getPid($order, $guider_id) {
        $guider_log = GuiderLog::where('shop_id', $order['shop_id'])
            ->where('memo', 'like', '%为'.$guider_id.')%')
            ->where('action_type', 0)
            ->where('created_at', '>', $order['created_at'])
            ->orderBy('id', 'asc')
            ->value('memo');
        if ($guider_log) {
            $this->guider_change[] = $guider_log;
            preg_match_all('/为(\d*)\)/', $guider_log, $match);
            $prePid = $match[1][1];
            if ($pid = Guider::where('shop_id', $order['shop_id'])->where('id', $prePid)
                ->where('status', '!=', 1)->where('created_at', '<', $order['created_at'])->value('id')) {
                return $pid;
            } else {
                return 0;
            }
        } else {
            return Guider::where('shop_id', $order['shop_id'])->where('id', $guider_id)
                ->value('parent_id');
        }
    }

    private function getGid($order, &$gid) {
        $guider_log = GuiderLog::where('shop_id', $order['shop_id'])
            ->where('memo', 'like', '%为'.$order['member_id'].')%')
            ->whereIn('action_type', [1,2])
            ->where('created_at', '>', $order['created_at'])
            ->orderBy('id', 'asc')
            ->value('memo');
        if ($guider_log) {
            $this->guider_change[] = $guider_log;
            preg_match_all('/为(\d*)\)/', $guider_log, $match);
            $gid = $match[1][1];
        }
    }

    private function getGuiderSelfBuy($order) {
        $logs = new Logs();
        $target_month = date('Ym', strtotime($order['created_at']));
        $now_month = date('Ym');
        $logs->setTable('logs_'.$target_month);


        while ($target_month <= $now_month && !isset($original)) {
            $original = $logs->where('merchant_id', $order['merchant_id'])
                ->where('source', 'guiderSetting')
                ->where('created_at', '>', $order['created_at'])
                ->orderBy('id', 'asc')
                ->take(1)
                ->value('original');
            if ($original) {
                $original = json_decode($original, true);
                $guider_self_buy = $original['guider_self_buy'];
            }
            $target_month = $this->nextMonth($target_month);
            $logs->setTable('logs_'.$target_month);
        }


        if (isset($guider_self_buy)) {
            return $guider_self_buy;
        }

        $guider_self_buy = GuiderSetting::where('shop_id', $order['shop_id'])->value('guider_self_buy');
        return $guider_self_buy;
    }

    private function nextMonth($ym) {
        $month = (int)substr($ym, 4, 2);
        $year = (int)substr($ym, 0, 4);
        if ($month == 12) {
            $month = 1;
            $year += 1;
        } else {
            $month += 1;
        }
        $month = sprintf('%02d', $month);

        return $year.$month;
    }
}
