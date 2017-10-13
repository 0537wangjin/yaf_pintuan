<?php

/**
 * Created by PhpStorm.
 * User: wangjin [445899710@qq.com]
 * Date: 2017/9/18
 */
class BaseController extends Yaf_Controller_Abstract
{
    protected $twig = null;
    protected $db = null;

    /**
     * init 初始化函数
     */
    protected function init()
    {
        $this->twig = Yaf_Registry::get('twig');
        $this->db = Yaf_Registry::get('db');
        // 设置所在城市
        if (!Yaf_Session::getInstance()->has('mycity')) {
            Help::setSession('mycity', $this->getIpCity());
        }
        // 设置默认城市
        if (!Yaf_Session::getInstance()->has('city')) {
            Help::setSession('city', Help::getSession('mycity'));
        }
    }

    /**
     * 根据积分计算level
     */
    protected function levelCalc($jifen)
    {
        $level = 1;
        $list = $this->db->select('sys_config_level', ['id', 'jifen'], ['jifen[>]' => 0, 'ORDER' => ['jifen' => 'ASC']]);
        $count = count($list);
        for ($i = 0; $i < $count; $i++) {
            if ($jifen > $list[$i]['jifen']) {
                $level = $list[$i]['id'];
            }
        }
        if ($level > $count) {
            $level = $count;
        }
        return $level;
    }

    /**
     * 订单状态  1：待付款 2：待发货  3：待收货  4：待评价  5：已完成  6：退款申请中  7：退款成功  8：已关闭   9:退款失败  10：已删除
     * @param $status
     */
    protected function orderStatus($status)
    {
        switch ($status) {
            case 1:
                return '待付款';
            case 2:
                return '待发货';
            case 3:
                return '待收货';
            case 4:
                return '待评价';
            case 5:
                return '已完成';
            case 6:
                return '退款申请中';
            case 7:
                return '退款成功';
            case 8:
                return '已关闭';
            case 9:
                return '退款失败';
            case 10:
                return '已删除';
            case 20:
                return '待成团';
        }
    }

    /**
     * 数字转汉字
     * @param $status
     * @return string
     */
    protected function numToHanzi($num)
    {
        switch ($num) {
            case 1:
                return '一';
            case 2:
                return '二';
            case 3:
                return '三';
            case 4:
                return '四';
            case 5:
                return '五';
            case 6:
                return '六';
            case 7:
                return '七';
            case 8:
                return '八';
            case 9:
                return '九';
            case 10:
                return '十';
        }
    }

    /**
     * 成功提示
     * @param $tip
     * @param string $backurl
     */
    protected function success($success = 1, $tip, $backurl = 'javascript:history.go(-1)')
    {
        $assign = array(
            'success' => $success,
            'tips' => $tip,
            'backurl' => $backurl
        );
        echo $this->twig->render('/error.html', $assign);
        die;
    }

    protected function getIpCity()
    {
        $keyword = !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] :
            (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] :
                (!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown'));
        $url = 'http://ip.taobao.com/service/getIpInfo.php?ip=' . $keyword;
        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 2
            )
        ));
        $obj = file_get_contents($url, 0, $context);
        $json = $this->object_array(json_decode($obj));
        if (!empty($json['data']['region'])) {
            // echo '本机IP: ' . $keyword . ' ' . $json['data']['region'] . '' . $json['data']['city'] . ' ' . $json['data']['isp'];
            return $json['data']['city'];
        } else {
            return '济南市';
        }
    }

    protected function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }

    /**
     * 把秒数转换为时分秒的格式
     * @param Int $times 时间，单位 秒
     * @return String
     */
    protected function secToTime($times)
    {
        $result = '00:00:00';
        if ($times > 0) {
            $hour = floor($times / 3600);
            $minute = floor(($times - 3600 * $hour) / 60);
            $second = floor((($times - 3600 * $hour) - 60 * $minute) % 60);
            $result = $hour . ':' . $minute . ':' . $second;
        }
        return $result;
    }
}