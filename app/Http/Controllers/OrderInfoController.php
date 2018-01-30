<?php

namespace App\Http\Controllers;

use App\Model\CreditDetail;
use App\Model\GuiderInvitation;
use App\Model\OrderUmp;
use App\Model\OrderGoods;
use App\Model\Trade;
use App\Model\GuiderOrder;
use App\Model\PartnerOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Model\Order;
use App\Model\OrderRefund;
use App\Model\GuiderOrderDetail;
use App\Model\Member;
use App\Model\GuiderSetting;


class OrderInfoController extends Controller
{

    public function orderInfo($order_sn, Request $request) {
        $order = Order::where('order_sn', $order_sn)->first();
        if (!$order) return Response::json(array('status' => false, 'errmsg' => '没有该订单'), 200);
        $member_name = Member::where('id', $order['member_id'])->value('name');
        $order['member_name'] = $member_name;
        $order_id = $order['id'];
        $guider_order = GuiderOrder::where('order_id', $order_id)->first();
        if ($guider_order) {
            $guider_order = GuiderOrderDetail::where('order_id', $order_id)->get();
        }
        $guider_self_buy = GuiderSetting::where('shop_id', $order['shop_id'])->value('guider_self_buy');
        if ($guider_self_buy == 1) {
            $gid = GuiderInvitation::where('member_id', $order['member_id'])
                ->where('type', 1)->where('shop_id', $order['shop_id'])->value('guider_id');

        }

        $partner_order = PartnerOrder::where('order_id', $order_id)->where('team_bonus', '>', 0)->first();
        $partner_order = $partner_order ? $partner_order : array();
        return Response::json(compact('order', 'partner_order', 'guider_order'), 200);
    }
}
