<?php

namespace App\Http\Controllers;

use App\Model\CreditDetail;
use App\Model\OrderUmp;
use App\Model\OrderGoods;
use App\Model\Trade;
use App\Model\GuiderOrder;
use App\Model\PartnerOrder;
use Illuminate\Http\Request;
use App\Model\Order;
use App\Model\OrderRefund;


class OrderRefundController extends Controller
{
    public function orderRefund($order_sn, Request $request) {
        set_time_limit(0);
        $param = $request->all();
        $abc = '';
        if (isset($param['xu']) && $param['xu'] == 1) {
            $abc .= 'xuboheng::';
        }
        \Log::info(date('Y-m-d H:i:s').':'.$abc.'orderRefund=>'.$order_sn);
        $ra = isset($param['ra']) ? $param['ra'] : 0;
        $hard = isset($param['hard']) ? $param['hard'] : 0;
        $tp = isset($param['tp']) ? $param['tp'] : 2;
        $sqlRes = Order::select('order.order_sn','order_goods.price','order_goods.pay_price','order.payment_code',
            'order_refund.amount','order.is_virtual','order.id','order.refund_status','order_goods.quantity','order_goods.shipped_quantity','order_goods.refund_quantity',
            'order_refund.refund_quantity as true_refund_quantity','order_refund.feedback','order_refund.feedback_sn','order.type','order.created_at','order_goods.goods_id',
            'order.member_id','order.is_sys','order.shop_id','order_refund.reason','order.merchant_id','order_goods.id as order_goods_id','order_refund.id as refund_id',
            'order.order_type','order.status','order_goods.status as order_goods_status','order_goods.refund_status as goods_refund_status','order_refund.status as ref_status')
            ->leftJoin('order_goods', 'order.id', '=', 'order_goods.order_id')
            ->leftJoin('order_refund', function($join)
            {
                $join->on('order.id', '=', 'order_refund.order_id')
                    ->on('order_goods.goods_id', '=', 'order_refund.goods_id')
                    ->on('order_goods.product_id', '=', 'order_refund.product_id');
            })
            ->where('order.order_sn', $order_sn)->get()->toArray();


        $has_shipped = array();
        $has_refunded = array();
        $correct_row = array();
        $target_row = array();
        $ship_ordergoods_id = array();
        $order_id = '';
        $member_id = '';
        if ($sqlRes) {
            foreach ($sqlRes as $key=>$row) {
                if ($row['goods_refund_status'] == 10 && $row['ref_status'] == 10) {
                    $target_row[] = $row;
                }
                $member_id = $row['member_id'];
                $order_id = $row['id'];
                $type = $row['type'];
                if ($row['shipped_quantity'] != 0) {
                    $has_shipped[] = $row;
                    if (($row['shipped_quantity'] == $row['quantity'] - $row['refund_quantity'])) {
                        if ($row['order_goods_status'] == 40 || $row['order_goods_status'] == 50) {
                            $correct_row[] = $row;
                        }
                        $ship_ordergoods_id[] = $row['order_goods_id'];
                    }
                } else {
                    if ($row['refund_quantity'] != 0) {
                        $has_refunded[] = $row;
                        if (($row['quantity'] == $row['refund_quantity']) && ($row['order_goods_status'] == 13)) {
                            $correct_row[] = $row;
                        }
                    } else {
                        if ($row['order_goods_status'] == 30 || $row['order_goods_status'] == 31) {
                            $correct_row[] = $row;
                        }
                    }
                }
            }
        }
        $correct = (count($correct_row) == count($sqlRes)) ? 'true' : 'false';
        echo "======================".'<br />';
        echo "Row Number:".count($sqlRes).'<br />';
        echo "correct_row:".count($correct_row).'<br />';
        echo "has_refunded:".count($has_refunded).'<br />';
        echo "has_shipped:".count($has_shipped).'<br />';
        echo "correct:".$correct.'<br />';
        echo "type:".$type.'<br />';
        echo "======================".'<br />';
//        if ($correct == 'true' && !$hard) {
//            die('订单正常');
//        }

        $where = array(
            'order_id' => $order_id
        );
        //order_id = '.$order_id.' AND status >= 10 AND status <30');
        $refund_amount = 0
            - OrderRefund::where('order_id', $order_id)
                ->where('status', '>=', '10')
                ->where('status', '<', '30')->sum('amount')
            - OrderRefund::where('order_id', $order_id)
                ->where('status', '>=', '10')
                ->where('status', '<', '30')->sum('shipment_fee');


        if ($tp != 2) {

            $other_pay = OrderUmp::where('type', 4)->where('order_id', $order_id)->sum('amount');
            $refund_amount = $refund_amount + $other_pay;
        }

        $refunded_amount = Trade::where('type', 1)->where('order_id', $order_id)->sum('total_fee');
        $refunded_amount = ($refunded_amount == NULL) ? 0 : $refunded_amount;
        $amount = Trade::where('type', 0)->where('order_id', $order_id)->sum('total_fee');

        $trade_info = Trade::where($where)->first();
        // echo 'refunded_amount:'.$refunded_amount.'<br />';
        // echo 'refund_amount:'.$refund_amount.'<br />';

        if ($ra) {
            $refund_amount = 0-$ra;
        }
        if (round($refunded_amount - $refund_amount, 2) > round($amount, 2)) {
            echo "======================".'<br />';
            echo '退款总额';var_dump(round($refunded_amount - $refund_amount, 2));
            echo '应退款';var_dump(round($amount, 2));
            echo "超额退款";exit;
        }

        $typeR = 1;
        if ($hard) {
            $check = OrderGoods::where(function($where) {
                $where->where('status', '!=', '13')
                    ->orWhere('refund_status', '!=', '31');
            })->where($where)->first();
            if ($check) {
                echo 'UPDATE order_goods SET `status` = 13,`refund_status` = 31,refund_quantity = quantity, updated_at = NOW() WHERE order_id = '.$order_id.';'.'<br />';
            }
        } else {
            if (count($sqlRes) == 1) {
                $check = OrderGoods::where(function($where) {
                    $where->where('status', '!=', '13')
                        ->orWhere('refund_status', '!=', '31');
                })->where($where)->first();
                if ($sqlRes[0]['true_refund_quantity'] > $sqlRes[0]['quantity']) {
                    die('退款数量大于购买数量');
                }
                if ($sqlRes[0]['true_refund_quantity'] > 0 && $sqlRes[0]['true_refund_quantity'] < $sqlRes[0]['quantity']) {
                    echo 'UPDATE order_goods SET `status` = 40,`refund_status` = 31,refund_quantity = '.$sqlRes[0]['true_refund_quantity'].', updated_at = NOW() WHERE order_id = '.$order_id.';'.'<br />';
                    $typeR = 2;
                } else {
                    if ($check) {
                        echo 'UPDATE order_goods SET `status` = 13,`refund_status` = 31,refund_quantity = quantity, updated_at = NOW() WHERE order_id = '.$order_id.';'.'<br />';
                    }
                }
            } else {
                $refund_ordergoods_id = array();
                $refund_refund_id = array();
                foreach ($correct_row as $row) {
                    if ($row['order_goods_status'] >= 30 && $row['refund_id']!=null) {
                        $refund_ordergoods_id[] = $row['order_goods_id'];
                        $refund_refund_id[] = $row['refund_id'];
                    }
                }
                foreach ($has_refunded as $row) {
                    $refund_ordergoods_id[] = $row['order_goods_id'];
                    $refund_refund_id[] = $row['refund_id'];
                }
                // print_r($correct_row);
                if (count($refund_ordergoods_id) == 0) {
                    die('havn\'t refund_ordergoods_id!');
                }

                // print_r($sqlRes);
                // print_r($refund_ordergoods_id);exit;
                if (count($refund_ordergoods_id)<count($sqlRes)) {
                    $ship_goods_id = implode(',', $ship_ordergoods_id);
                    $ship_goods_id = '('.$ship_goods_id.')';
                    $check = OrderGoods::where(function($where) {
                        $where->where('status', '!=', '13')
                            ->orWhere('refund_status', '!=', '31');
                    })->whereIn('id', $refund_ordergoods_id)->first();
                    if ($check) {
                        $goods_id = '('.implode(',', $refund_ordergoods_id).')';
                        echo 'UPDATE order_goods SET `status` = 13,`refund_status` = 31,refund_quantity = quantity, updated_at = NOW() WHERE id in '.$goods_id.';'.'<br />';
                    }
                    if (count($ship_ordergoods_id)) {
                        $check = OrderGoods::where('status', '<', '40')->whereIn('id', $ship_ordergoods_id)->first();
                        if ($check) {
                            echo 'UPDATE order_goods SET `status` = 40, updated_at = NOW() WHERE id in '.$ship_goods_id.';'.'<br />';
                        }
                    }
                    $check = OrderRefund::where('status', '!=', '31')->whereIn('id', $refund_refund_id)->first();
                    if ($check) {
                        $refund_id = '('.implode(',', $refund_refund_id).')';
                        echo 'UPDATE `order_refund` SET status = 31 , feedback = 1 , memo = \'手动完成退款\' , operated_at = NOW() , finished_at=NOW() , updated_at=NOW() WHERE id in '.$refund_id.';'.'<br />';
                    }
                    $typeR = 2;
                } else {
                    $check = OrderGoods::where(function($where) {
                        $where->where('status', '!=', '13')
                            ->orWhere('refund_status', '!=', '31');
                    })->where($where)->first();
                    if ($check) {
                        echo 'UPDATE order_goods SET `status` = 13,`refund_status` = 31,refund_quantity = quantity, updated_at = NOW() WHERE order_id = '.$order_id.';'.'<br />';
                    }
                }
            }
        }
        $where = array(
            'id' => $order_id
        );
        if ($typeR == 2) {
            $check = Order::where($where)->where('status', '<', 40)->first();
            if ($check) {
                echo 'UPDATE `order` SET `status` = 40,`memo` = \'手动发货\',shipments_at = NOW(), updated_at = NOW() WHERE order_id = '.$order_id.';'.'<br />';
            }

        } else {
            $check = Order::where($where)->where('status', '!=', 13)->first();
            if ($check) {
                echo 'UPDATE `order` SET `status` = 13,`memo` = \'手动退款\',shipments_at = NOW(), updated_at = NOW() WHERE order_id = '.$order_id.';'.'<br />';
            }

            $where = array(
                'order_id' => $order_id
            );
            $check = OrderRefund::where($where)->where('status', '!=', 31)->first();
            if ($check) {
                echo 'UPDATE order_refund SET `status` = 31,`memo` = \'手动完成退款\',`feedback` = 1, operated_at = NOW(), finished_at = NOW(), updated_at = NOW() WHERE order_id = '.$order_id.';'.'<br />';
            }

            $check = GuiderOrder::where($where)->where('status', 0)->first();
            if ($check && $typeR == 1) {
                echo 'UPDATE guider_order SET status=2,updated_at = NOW() where order_id = '.$order_id.';'.'<br />';
                echo 'UPDATE guider_order_detail SET refund_comission = comission,updated_at = NOW() where order_id = '.$order_id.';'.'<br />';
            }

            $check = PartnerOrder::where($where)->where('status', 0)->first();
            if ($check && $typeR == 1) {
                echo 'UPDATE partner_order SET status=2,updated_at = NOW() where order_id = '.$order_id.';'.'<br />';
            }
        }




        if ($refund_amount<0) {
            echo 'INSERT INTO `trade` ( `pid`, `from_merchant_id`, `merchant_id`, `from_shop_id`, `shop_id`,
`top_merchant_id`, `top_shop_id`, `order_id`, `order_sn`, `payment_name`, `code`, `payment_sn`,
`subject`, `total_fee`, `balance`, `is_sys`, `is_supplier`, `type`, `status`, `trade_sn`, `created_at`, `updated_at`, `settled_at`, `remarks`) VALUES
(\'0\', \'0\', \''.$trade_info['merchant_id'].'\', \'0\', \''.$trade_info['shop_id'].'\',
\'0\', \'0\', \''.$order_id.'\', \''.$trade_info['order_sn'].'\', \''.$trade_info['payment_name'].'\', \''.$trade_info['code'].'\', \''.$trade_info['payment_sn'].'\',
\'订单退款\', \''.$refund_amount.'\', \'0.00\', \'0\', \'0\', \'1\', \'0\', \'\', NOW(), NOW(), \'0000-00-00 00:00:00\', "商户线下退款");'.'<br />';
        }

        if ($type == 3) {
            echo '<br />';
            echo '#wxrrd_pintuan#'.'<br />';
            echo 'UPDATE tuan_refund SET status = 2 WHERE order_id = '.$order_id.';'.'<br />';
            echo 'UPDATE tuan_initiate_join SET status = 40 WHERE order_id = '.$order_id.';'.'<br />';
        }

        $credit_amount = OrderUmp::where($where)->where('type', 3)->sum('amount');
        if ($credit_amount > 0) {
            $credit_credit = CreditDetail::select(\DB::Raw('sum(credit)'),'credit')->where('member_id', (int)$member_id)->where('memo', 'like', '%'.$credit_amount.'%')->first();
            if ($credit_credit['sum(credit)'] < 0) {
                echo '#'.($member_id+1000002016).':\'退款退积分'.-$credit_credit['credit'].'\'';
            }
        }
    }
}
