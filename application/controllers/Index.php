<?php
/**
 * Created by PhpStorm.
 * User: wangjin [445899710@qq.com]
 * Date: 2017/9/18
 */

/**
 * @name IndexController
 * @author root
 * @desc 默认控制器
 */
class IndexController extends BaseController
{
    /**
     * 设置默认城市 - ajax
     */
    public function setCityAction()
    {
        $city = Help::getg('city');
        Help::setSession('city', $city);
        die('success');
    }

    public function indexAction()
    {
        $assign = array(
            'pagetitle' => '金盛微商城',
            'active' => 'index',
        );
        // 当前城市
        $mycity = Help::getSession('mycity');
        $assign['mycity'] = $mycity;
        // 开通城市
        $district = $this->db->select('sys_cascade_district', ['charindex'], ['GROUP' => 'charindex', 'status' => 1, 'ORDER' => ['charindex']]);
        for ($i = 0; $i < count($district); $i++) {
            $district[$i]['city'] = $this->db->select('sys_cascade_district', ['id', 'name', 'charindex'], ['status' => 1, 'charindex' => $district[$i]['charindex']]);
        }
        $assign['district'] = $district;
        if (!Yaf_Session::getInstance()->has('city')) {
            Help::setSession('city', $mycity);
        }
        $assign['city'] = Help::getSession('city');
        // Category
        $category = $this->db->select('sys_goods_category', ['id', 'name', 'orderby', 'imgurl'], ['parentid' => 0, 'ORDER' => ['orderby']]);
        $assign['category'] = $category;

        // AD
        $ad = $this->db->select('sys_ad', ['id', 'imgurl', 'url', 'imgurl'], ['ORDER' => ['ad_order']]);
        $assign['ad'] = $ad;

        // 推荐商品
        $goods = $this->db->select('sys_goods', ['id', 'name', 'rush', 'rushprice', 'price', 'imgurl', 'secondtype'], ['status' => 1,'rec' => 1, 'ORDER' => ['id' => 'DESC'], 'LIMIT' => 30, 'city' => ['', $assign['city']]]);

        $assign['goods'] = $goods;

        // 每日签到积分
        $signconfig = $this->db->select('sys_sign', ['days', 'coins']);
        $assign['signconfig'] = $signconfig;

        // 我的签到信息
        $sign = array('id' => '', 'client_id' => '', 'lasttime' => '', 'days' => '', 'today' => 0);
        if (Yaf_Session::getInstance()->has('userid')) {
            $uid = (int)Help::getSession('userid');
            $sign = $this->db->get('sys_sign_check', ['id', 'client_id', 'lasttime', 'days'], ['client_id' => $uid]);
            $nowdate = date('Ymd', time());
            if (!empty($sign)) {
                // 检测今天是否签到
                if ($sign['lasttime'] == $nowdate) {
                    $sign['today'] = 1;
                }
            }

        }
        $assign['sign'] = $sign;

        // render
        echo $this->twig->render('/index/index.html', $assign);
    }

    /**
     * 商品详情
     */
    public function goodsAction()
    {
        $assign = array('active' => 'index', 'fav' => 0);
        // param
        $id = intval(Help::getg('id'));
        if (empty($id)) {
            die('参数错误');
        }
        // 获取商品信息
        $goods = $this->db->get('sys_goods', '*', ['id' => $id]);
        if (empty($goods)) {
            die('参数错误');
        }
        if ($goods['rush'] > 0) {
            $goods['rushtime'] = date('Y/m/d H:i', $goods['rushtime']);
        }

        $assign['goods'] = $goods;
        // 获取轮播图
        $photo = $this->db->select('sys_goods_photo', '*', ['goodsid' => $id, 'ORDER' => ['px']]);

        $assign['photo'] = $photo;
        // 获取评价
        $reply = $this->db->select('sys_goods_reply', '*', ['goodsid' => $id, 'LIMIT' => 20, 'ORDER' => ['regdate' => 'DESC']]);
        foreach ($reply as $key => $val) {
            $reply[$key]['regdate'] = Help::formatTime($reply[$key]['regdate']);
        }
        $assign['reply'] = $reply;
        if (!empty($reply)) {
            // 获取评价总数
            $reply_count = $this->db->count("sys_goods_reply", [
                "goodsid" => $id
            ]);
            $assign['reply_count'] = $reply_count;
            // 平均评价
            $reply_sum = $this->db->sum("sys_goods_reply", ['stars'], [
                "goodsid" => $id
            ]);
            $assign['reply_avg'] = intval($reply_sum / $reply_count);
        }
        // 有没有收藏
        if (Yaf_Session::getInstance()->has('userid')) {
            $uid = Help::getSession('userid');
            $ifhas = $this->db->has('sys_goods_fav', ['cid' => $uid, 'goodsid' => $id]);
            if ($ifhas) {
                $assign['fav'] = '1';
            }
        }
        // 更新点击
        $this->db->update('sys_goods', ['browsenum[+]' => 1], ['id' => $id]);
        //
        $assign['pagetitle'] = $goods['name'];
        // 拼团商品
        if ($goods['rush'] > 0) {
            // 获取参与列表
            $rushlist = $this->db->select('sys_goods_rush', ['[>]sys_client' => ['uid' => 'id']], ['sys_client.nickname', 'sys_client.avatar', 'sys_goods_rush.id', 'sys_goods_rush.uid', 'sys_goods_rush.pid', 'sys_goods_rush.status', 'sys_goods_rush.regdate'], ['sys_goods_rush.goodsid' => $goods['id'], 'sys_goods_rush.pid' => 0, 'sys_goods_rush.status' => 1, 'LIMIT' => 3]);
            foreach ($rushlist as $key => $val) {
                // 统计参与人数
                $total = $this->db->count('sys_goods_rush', ['pid' => $rushlist[$key]['id']]);
                $total += 1;
                // 计算剩余人数
                $rushlist[$key]['surplus'] = $this->numToHanzi($goods['rushnum'] - $total);
                // 倒计时
                $rushlist[$key]['sec'] = $this->secToTime($rushlist[$key]['regdate'] + 86400 - time());
            }
            $assign['rushlist'] = $rushlist;
            //获取开团总人数
            $assign['rushcount'] = $this->db->count('sys_goods_rush', ['goodsid' => $id, 'status' => 1, 'pid' => 0]);

            // render
            echo $this->twig->render('/index/goods_tuan_details.html', $assign);
        } else {
            echo $this->twig->render('/index/goods_details.html', $assign);
        }
    }

    /**
     * 微信一键登录,注册
     */
    public function registerAction()
    {
        // $channelid = Help::getg('openid');
        $channelid = Help::randStr(16);
        if (empty($channelid)) {
            die('参数错误');
        }
        $channelid = 'a100001';
        // 检索用户信息
        $user = $this->db->get('sys_client', ['id', 'nickname', 'avatar', 'province', 'city', 'area', 'openid'], ['openid' => $channelid]);

        // 注册用户 insert into sys_client(username, channelid) values('1001', '1001')
        if (empty($user)) {
            $regdate = Help::hdate('Y-m-d H:i:s', time());
            $this->db->insert("sys_client", [
                'username' => $channelid,
                'openid' => $channelid,
                'regdate' => $regdate,
                'lastlogintime' => $regdate,
            ]);
            $lastid = $this->db->id();
            // 重新获取用户信息
            $user = $this->db->get('sys_client', ['id', 'nickname', 'avatar', 'province', 'city', 'area', 'openid'], ['id' => $lastid]);
        }
        Help::setSession('userid', $user['id']);
        Help::setSession('openid', $user['openid']);
        Help::setSession('nickname', $user['nickname']);
        Help::setSession('avatar', $user['avatar']);

        if (Help::getg('backurl')) {
            $this->redirect(Help::getg('backurl'));
        } else {
            $this->redirect('/my/index');
        }
    }

    /**
     * Redis使用方法
     */
    public function redisAction()
    {
        $redis = new \Redis();
        $redis->connect(Yaf_Application::app()->getConfig()->application->redis->host, Yaf_Application::app()->getConfig()->application->redis->port);

        $redis->set('name', time());
        echo $redis->get('name');
        die;
    }

    /**
     * 添加取消收藏 - ajax
     */
    public function favAddAction()
    {
        $id = intval(Help::getg('id'));
        if (empty($id)) {
            die('参数错误');
        }
        // 判断登录
        if (!Yaf_Session::getInstance()->has('userid')) {
            die('请先登录!');
        }
        $uid = Help::getSession('userid');
        // 检查有没有收藏商品
        $ifhas = $this->db->has('sys_goods_fav', ['cid' => $uid, 'goodsid' => $id]);
        if ($ifhas) {
            // 取消收藏
            $this->db->delete('sys_goods_fav', ['cid' => $uid, 'goodsid' => $id]);
        } else {
            // 新增收藏
            $this->db->insert('sys_goods_fav', array(
                'cid' => $uid,
                'goodsid' => $id,
                'keytype' => 1,
            ));
        }
        die('success');
        //
    }


    /**
     * 评价商品
     */
    public function evaluateListAction()
    {
        $assign = array(
            'pagetitle' => '全部评价',
        );
        // param
        $id = intval(Help::getg('id'));
        if (empty($id)) {
            die('参数错误');
        }
        $assign['goodsid'] = $id;
        // 获取商品信息
        $goods = $this->db->has('sys_goods', ['id' => $id]);
        if (empty($goods)) {
            die('参数错误');
        }
        // 获取评价
        $reply = $this->db->select('sys_goods_reply', '*', ['goodsid' => $id, 'LIMIT' => 100, 'ORDER' => ['regdate' => 'DESC']]);
        foreach ($reply as $key => $val) {
            $reply[$key]['regdate'] = Help::formatTime($reply[$key]['regdate']);
        }
        $assign['reply'] = $reply;
        if (!empty($reply)) {
            // 获取评价总数
            $reply_count = $this->db->count("sys_goods_reply", [
                "goodsid" => $id
            ]);
            $assign['reply_count'] = $reply_count;
            // 平均评价
            $reply_sum = $this->db->sum("sys_goods_reply", ['stars'], [
                "goodsid" => $id
            ]);
            $assign['reply_avg'] = intval($reply_sum / $reply_count);
        }

        echo $this->twig->render('/index/evaluationAll.html', $assign);
    }

    /**
     * 添加到购物车
     */
    public function cartAddAction()
    {
        $sessionid = Help::getSessionID();
        // param
        $goodsid = intval(Help::getg('goodsid'));
        $num = intval(Help::getg('num'));
        if (empty($goodsid)) {
            die('参数错误');
        }
        $assign['goodsid'] = $goodsid;
        // 获取商品信息
        $goods = $this->db->get('sys_goods', ['id', 'name', 'imgurl', 'price', 'stock'], ['id' => $goodsid]);
        if (empty($goods)) {
            die('参数错误');
        }
        // 判断库存
        if ($goods['stock'] < $num) {
            die('库存不足!');
        }
        // 检查购物车里有没有这件商品
        $ifhas = $this->db->get('sys_cart', '*', ['session_id' => $sessionid, 'goodsid' => $goodsid]);
        if (empty($ifhas)) {
            $goods['totalprice'] = $goods['price'];
            if ($num < 1) {
                $num = 1;
            } else {
                $goods['totalprice'] = $goods['price'] * $num;
            }
            // 插入到购物车
            $param = array(
                'session_id' => $sessionid,
                'propertyid' => '',
                'propertyname' => '',
                'goodsid' => $goodsid,
                'buycount' => $num,
                'name' => $goods['name'],
                'imgurl' => $goods['imgurl'],
                'price' => $goods['totalprice'],
                'goodsprice' => $goods['price'],
                'regdate' => time(),
            );
            $res = $this->db->insert('sys_cart', $param);
            if ($this->db->id() > 0) {
                die('success');
            }
        } else {
            if ($num < 1) {
                $num = 1;
            }
            // 重新计算总价
            $price = $ifhas['goodsprice'] * ($ifhas['buycount'] + $num);
            // 更新商品数量
            $res = $this->db->update('sys_cart', ['price' => $price, 'buycount[+]' => $num, 'regdate' => time()], ['session_id' => $sessionid, 'goodsid' => $goodsid]);
            if ($res) {
                die('success');
            }
        }
    }

    /**
     * 拼团 - 添加到购物车
     */
    public function cartAddRushAction()
    {
        $sessionid = Help::getSessionID();
        // param
        $goodsid = intval(Help::getg('goodsid'));
        $num = 1;
        if (empty($goodsid)) {
            die('参数错误');
        }
        $assign['goodsid'] = $goodsid;
        // 获取商品信息
        $goods = $this->db->get('sys_goods', ['id', 'name', 'imgurl', 'price', 'stock', 'rush', 'rushprice'], ['id' => $goodsid]);
        if (empty($goods)) {
            die('参数错误');
        }
        // 判断是否是拼团商品
        if ($goods['rush'] < 1) {
            die('非法操作!');
        }
        // 判断库存
        if ($goods['stock'] < $num) {
            die('库存不足!');
        }
        // 清空购物和中的拼团商品
        $this->db->delete('sys_cart', ['session_id' => $sessionid, 'rush' => 1]);
        // 检查购物车里有没有这件商品
        $ifhas = $this->db->get('sys_cart', '*', ['session_id' => $sessionid, 'goodsid' => $goodsid]);
        if (empty($ifhas)) {
            $goods['totalprice'] = $goods['rushprice'];
            // 插入到购物车
            $param = array(
                'session_id' => $sessionid,
                'propertyid' => '',
                'propertyname' => '',
                'goodsid' => $goodsid,
                'buycount' => $num,
                'name' => $goods['name'],
                'imgurl' => $goods['imgurl'],
                'price' => $goods['totalprice'],
                'goodsprice' => $goods['rushprice'],
                'rush' => 1,
                'regdate' => time(),
            );
            $res = $this->db->insert('sys_cart', $param);
            if ($this->db->id() > 0) {
                die('success');
            }
        } else {
            // 重新计算总价
            $price = $ifhas['goodsprice'];
            // 更新商品数量
            $res = $this->db->update('sys_cart', ['rush' => 1, 'price' => $price, 'buycount' => $num, 'regdate' => time()], ['session_id' => $sessionid, 'goodsid' => $goodsid]);
            if ($res) {
                die('success');
            }
        }
    }

    /**
     * 领券中心
     */
    public function couponAction()
    {
        $assign = array(
            'pagetitle' => '领券',
        );
        //
        $list = $this->db->select('sys_coupon', '*', ['status' => 0, 'ORDER' => ['regdate' => 'DESC']]);
        foreach ($list as $key => $val) {
            $list[$key]['endtime'] = date('Y-m-d', $list[$key]['endtime']);
        }
        //
        $assign['list'] = $list;
        echo $this->twig->render('/index/coupon.html', $assign);
    }

    /**
     * 领券
     */
    public function couponGetAction()
    {
        $id = intval(Help::getg('id'));
        if (empty($id)) {
            die('参数错误!');
        }
        $uid = 0;
        // 如果没有登录,跳转到登录
        if (!Yaf_Session::getInstance()->has('userid')) {
            die('请先登录!');
        } else {
            $uid = Help::getSession('userid');
        }
        // 判断优惠券存不存在
        $coupon = $this->db->get('sys_coupon', '*', ['id' => $id]);
        if (empty($coupon)) {
            die('参数错误!');
        }
        // 判断用户是否领取过
        $usercoupon = $this->db->get('sys_coupon_client', '*', ['cid' => $id, 'client_id' => $uid]);
        if (!empty($usercoupon)) {
            die('您已经领取过了!');
        }
        // 开始发放优惠券
        if ($coupon['havnum'] < $coupon['nums']) {
            $res = $this->db->action(function ($db) use ($id, $uid) {
                // 更新havnum
                $sql1 = $db->update('sys_coupon', ['havnum[+]' => 1], ['id' => $id]);
                if ($sql1->rowCount() < 1) {
                    return false;
                }
                // 发放
                $sql2 = $db->insert('sys_coupon_client', ['cid' => $id, 'client_id' => $uid]);
                if ($sql2->rowCount() < 1) {
                    return false;
                }
            });
            if ($res == true) {
                die('领券成功!');
            } else {
                die('领券出错!');
            }

        } else {
            die('优惠券已经被抢光了!');
        }
    }

    /**
     * 签到
     */
    public function signAction()
    {
        // 检测是否登录
        if (!Yaf_Session::getInstance()->has('userid')) {
            die('请先登录!');
        }
        $nowdate = date('Ymd', time());
        $uid = (int)Help::getSession('userid');
        // 检测签到数据
        $sign = $this->db->get('sys_sign_check', ['id', 'client_id', 'lasttime', 'days'], ['client_id' => $uid]);
        if (empty($sign)) {
            // 第一次签到, 新增记录
            $param = array(
                'client_id' => $uid,
                'lasttime' => $nowdate,
                'days' => 1,
            );
            $this->db->insert('sys_sign_check', $param);
        }
        // 检测今天是否签到
        if ($sign['lasttime'] == $nowdate) {
            die('今日已签到!');
        }


        // 检测连续签到
        if ($sign['lasttime'] == date("Ymd", strtotime("-1 day"))) {
            $tempday = intval($sign['days']) + 1;
            // 如果连续签到大于7天
            if ($tempday > 7) {
                $tempday = 7;
            }
            $config = $this->db->get('sys_sign', ['coins'], ['days' => $tempday]);
            $coins = (int)$config['coins'];

            // 开始事务
            $res = $this->db->action(function ($db) use ($nowdate, $uid, $coins) {
                // 更新签到表数据
                $sql1 = $db->update('sys_sign_check', ['lasttime' => $nowdate, 'days[+]' => 1], ['client_id' => $uid]);
                if ($sql1->rowCount() < 1) {
                    return false;
                }
                // 新增积分记录
                $sql2 = $db->insert('sys_client_score', ['client_id' => $uid, 'title' => '签到', 'regdate' => time(), 'num' => $coins]);
                if ($sql2->rowCount() < 1) {
                    return false;
                }
                // 更新用户表score值
                $sql3 = $db->update('sys_client', ['score[+]' => $coins], ['id' => $uid]);
                if ($sql3->rowCount() < 1) {
                    return false;
                }
            });
            if ($res == true) {
                die('签到成功!');
            } else {
                die('系统出错!');
            }

        } else {
            // 非连续签到
            // 获取签到配置表 - 签到一天送的积分
            $config = $this->db->get('sys_sign', ['coins'], ['days' => 1]);
            $coins = (int)$config['coins'];
            // 开始事务
            $res = $this->db->action(function ($db) use ($nowdate, $uid, $coins) {
                // 更新签到表数据
                $sql1 = $db->update('sys_sign_check', ['lasttime' => $nowdate, 'days' => 1], ['client_id' => $uid]);
                if ($sql1->rowCount() < 1) {
                    return false;
                }
                // 新增积分记录
                $sql2 = $db->insert('sys_client_score', ['client_id' => $uid, 'title' => '签到', 'regdate' => time(), 'num' => $coins]);
                if ($sql2->rowCount() < 1) {
                    return false;
                }
                // 更新用户表score值
                $sql3 = $db->update('sys_client', ['score[+]' => $coins], ['id' => $uid]);
                if ($sql3->rowCount() < 1) {
                    return false;
                }
            });
            if ($res == true) {
                die('签到成功!');
            } else {
                die('系统出错!');
            }

        }
    }

    /**
     * 积分兑换列表
     */
    public function goldAction()
    {
        $assign = array(
            'pagetitle' => '积分兑换',
        );
        $list = $this->db->select('sys_score_goods', ['id', 'title', 'coins', 'imgurl']);
        $assign['list'] = $list;
        echo $this->twig->render('/index/gold.html', $assign);
    }

    /**
     * 积分商品详情
     */
    public function goldDetailsAction()
    {
        $id = intval(Help::getg('id'));
        if (empty($id)) {
            die('参数错误');
        }
        $assign = array();
        // 获取商品详情
        $goods = $this->db->get('sys_score_goods', ['id', 'title', 'coins', 'remark', 'fname'], ['id' => $id]);
        if (empty($goods)) {
            die('参数错误');
        }
        $assign['goods'] = $goods;
        $assign['pagetitle'] = $goods['title'];
        // 获取商品图片列表
        $photolist = $this->db->select('sys_score_goods_photo', ['id', 'imgurl'], ['gid' => $id, 'ORDER' => 'px']);
        $assign['photolist'] = $photolist;
        // render
        echo $this->twig->render('/index/goldDetails.html', $assign);
    }

    /**
     * 积分订单确认
     */
    public function goldConfirmAction()
    {
        $id = intval(Help::getg('id'));
        if (empty($id)) {
            die('参数错误');
        }
        // 检测是否登录
        if (!Yaf_Session::getInstance()->has('userid')) {
            $this->success(0, '请先登录!');
        }
        $assign = array(
            'pagetitle' => '确认订单',
        );
        $uid = (int)Help::getSession('userid');

        // 获取商品详情
        $goods = $this->db->get('sys_score_goods', ['id', 'title', 'coins', 'remark', 'fname'], ['id' => $id]);
        if (empty($goods)) {
            die('参数错误');
        }
        $assign['goods'] = $goods;
        // 检查积分
        $user = $this->db->get('sys_client', ['score'], ['id' => $uid]);
        if ($user['score'] < $goods['coins']) {
            $this->success(0, '积分不足!');
        }
        // 获取收货地址
        $assign['address'] = $this->db->get('sys_address', '*', ['client_id' => $uid, 'ORDER' => ['rec' => 'DESC']]);
        echo $this->twig->render('/index/goldConfirm.html', $assign);
    }

    public function goldSubmitAction()
    {
        $id = intval(Help::getg('id'));// 商品ID
        $num = intval(Help::getg('num'));// 数量
        $addressid = intval(Help::getg('address'));// 收货地址ID
        if (empty($id) || empty($num) || empty($addressid)) {
            die('参数错误1');
        }
        // 检测是否登录
        if (!Yaf_Session::getInstance()->has('userid')) {
            die('请先登录!');
        }
        $uid = (int)Help::getSession('userid');
        // 获取积分订单信息
        $goods = $this->db->get('sys_score_goods', ['id', 'title', 'coins', 'remark', 'fname', 'imgurl'], ['id' => $id]);
        if (empty($goods)) {
            die('参数错误2');
        }
        // 验证积分
        $score = $goods['coins'] * $num;
        $user = $this->db->get('sys_client', ['score'], ['id' => $uid]);
        if ($user['score'] < $score) {
            die('积分不足!');
        }
        // 插入到积分订单
        $param = array(
            'client_id' => $uid,
            'transid' => $this->transScoreTransID(),
            'status' => 2,
            'buycount' => $num,
            'goodsid' => $id,
            'money' => $score,
            'addressid' => $addressid,
            'regdate' => time(),
            'goodsname' => $goods['title'],
            'imgurl' => $goods['imgurl'],
        );
        // 开始事务
        $res = $this->db->action(function ($db) use ($param) {
            $sql1 = $db->insert('sys_score_order', $param);
            if ($sql1->rowCount() < 1) {
                return false;
            }
            // 更新用户积分
            $sql2 = $db->update('sys_client', ['score[-]' => $param['money']], ['id' => $param['client_id']]);
            if ($sql2->rowCount() < 1) {
                return false;
            }
            // 新增积分记录
            $sql3 = $db->insert('sys_client_score', ['client_id' => $param['client_id'], 'title' => '积分兑换', 'regdate' => time(), 'num' => -$param['money']]);
            if ($sql3->rowCount() < 1) {
                return false;
            }
        });
        if ($res == true) {
            die('success');
        } else {
            die('系统出错!');
        }
    }

    /**
     * 生成订单号
     * @return string
     */
    public function transScoreTransID()
    {
        $transid = date('YmdHis') . Help::randStr(6, 'NUMBER');
        $ifhas = $this->db->has('sys_score_order', ['transid' => $transid]);
        if (!empty($ifhas)) {
            return $this->transScoreTransID();
        }
        return $transid;
    }

    /**
     * 生成订单号
     * @return string
     */
    public function transRushTransID()
    {
        $transid = date('YmdHis') . Help::randStr(6, 'NUMBER');
        $ifhas = $this->db->has('sys_rush_order', ['transid' => $transid]);
        if (!empty($ifhas)) {
            return $this->transRushTransID();
        }
        return $transid;
    }

    /**
     * 商品列表
     */
    public function newAction()
    {
        $assign = array(
            'pagetitle' => '新品发布',
        );
        // 分类
        $cateid = intval(Help::getg('cateid'));
        $assign['catename'] = '全部';
        if ($cateid > 0) {
            $catename = $this->db->get('sys_goods_category', ['name'], ['id' => $cateid]);
            $assign['cateid'] = $cateid;
            $assign['catename'] = $catename['name'];
        }
        // 是否参团
        $rush = intval(Help::getg('rush'));
        $assign['rush_text'] = '参团';
        if ($rush == 1) {
            $assign['rush'] = $rush;
            $assign['rush_text'] = '参团';
        }
        if ($rush == 2) {
            $assign['rush'] = $rush;
            $assign['rush_text'] = '不参团';
        }
        // 销量
        $sales = intval(Help::getg('sales'));
        if ($sales == 1) {
            $assign['sales'] = $sales;
        }
        // 价格
        $price = intval(Help::getg('price'));
        if ($price == 1) {
            $assign['price'] = $price;
        }
        // 搜索 - 关键词
        $keyword = Help::getg('keyword');
        if (!empty($keyword)) {
            $assign['pagetitle'] = '搜索结果';
            $assign['keyword'] = $keyword;
        }
        // 获取分类列表
        $catelist = $this->db->select('sys_goods_category', ['id', 'name'], ['parentid' => 0]);
        $assign['catelist'] = $catelist;
        echo $this->twig->render('/index/new.html', $assign);
    }

    /**
     * 商品列表 Json
     */
    public function newJsonAction()
    {
        $cateid = intval(Help::getg('cateid'));
        $page = intval(Help::getg('page'));
        $shownum = 5;
        $start_num = $page * $shownum;
        $where = array();

        // param
        $where['ORDER'] = ['id' => 'DESC'];
        $where['LIMIT'] = [$start_num, $shownum];
        $where['status'] = 1;
        // 分类
        if ($cateid > 0) {
            $cateinfo = $this->db->get('sys_goods_category', ['parentid'], ['id' => $cateid]);
            // $where['OR'] = ['firsttypeid' => $cateid, 'secondtypeid' => $cateid];
            if ($cateinfo['parentid'] > 0) {
                $where['secondtypeid'] = $cateid;
            } else {
                $where['firsttypeid'] = $cateid;
            }
        }
        // 是否参团
        $rush = intval(Help::getg('rush'));
        if ($rush == 1) {
            $where['rush'] = 1;// 参团
            // 如果是拼团商品, 判断拼团时间
            $where['rushtimebegin[<]'] = time();
            $where['rushtime[>]'] = time();
        }
        if ($rush == 2) {
            $where['rush'] = 0;// 不参团
        }
        // 人气
        $hot = intval(Help::getg('hot'));
        if ($hot == 1) {
            $where['ORDER'] = ['browsenum' => 'DESC'];
        }
        // 销量
        $sales = intval(Help::getg('sales'));
        if ($sales == 1) {
            $where['ORDER'] = ['realsale' => 'DESC'];
        }
        //
        $price = intval(Help::getg('price'));
        if ($price == 1) {
            $where['ORDER'] = ['price' => 'ASC'];
        }
        // 搜索关键词
        $keyword = Help::getg('keyword');
        if (!empty($keyword)) {
            $where['name[~]'] = $keyword;
        }
        $city = Help::getSession('city');
        $where['city'] = ['', $city];
        $where['status'] = 1;

        $list = $this->db->select('sys_goods', ['id', 'name', 'imgurl', 'secondtypeid', 'secondtype', 'price', 'rush', 'rushprice', 'rec'], $where);
        // 获取订单商品
        foreach ($list as $key => $val) {
            //
        }
        echo json_encode($list);
        die;
    }

    /**
     * 拼团订单创建
     */
    public function rushOrderSubmitAction()
    {
        $sessionid = Help::getSessionID();
        $addressid = intval(Help::getg('address'));// 收货地址ID
        $rushid = intval(Help::getg('rushid'));// 收货地址ID
        if (empty($addressid)) {
            $this->success(0, '请选择收货地址!');
        }
        // 检测是否登录
        if (!Yaf_Session::getInstance()->has('userid')) {
            $this->success(0, '请先登录!');
        }
        $uid = (int)Help::getSession('userid');
        // 获取积分订单信息
        $cart = $this->db->get('sys_cart', '*', ['session_id' => $sessionid, 'rush' => 1]);
        if (empty($cart)) {
            $this->success(0, '购物车里没有任何东西!');
        }

        // 获取商品信息
        $goods = $this->db->get('sys_goods', ['rushnum'], ['id' => $cart['goodsid']]);
        // 插入到rush订单
        $param = array(
            'client_id' => $uid,
            'transid' => $this->transRushTransID(),
            'status' => 1,
            'buycount' => $cart['buycount'],
            'goodsid' => $cart['goodsid'],
            'money' => $cart['price'],
            'addressid' => $addressid,
            'regdate' => time(),
            'goodsname' => $cart['name'],
            'imgurl' => $cart['imgurl'],
            'rushnum' => $goods['rushnum'],
        );
        // 开始事务
        $res = $this->db->action(function ($db) use ($param, $rushid) {
            $sql2 = $db->insert('sys_goods_rush', array(
                'goodsid' => $param['goodsid'],
                'uid' => $param['client_id'],
                'regdate' => $param['regdate'],
                'pid' => $rushid,
            ));
            if ($sql2->rowCount() < 1) {
                return false;
            }
            $rushid = $db->id();
            $param['rushid'] = $rushid;
            $sql1 = $db->insert('sys_rush_order', $param);
            if ($sql1->rowCount() < 1) {
                return false;
            }

        });
        if ($res == true) {
            $this->redirect('/cart/paymentRush?transid=' . $param['transid']);
        } else {
            $this->success(0, '系统出错!');
        }
    }

    public function rushDetailsAction()
    {
        $assign = array(
            'pagetitle' => '参团',
        );
        $rushid = intval(Help::getg('rushid'));
        if ($rushid < 1) {
            $this->success(0, '参数错误!');
        }
        // 获取rush商品信息
        $rush = $this->db->get('sys_goods_rush', '*', ['id' => $rushid]);
        // 获取商品信息
        $goods = $this->db->get('sys_goods', '*', ['id' => $rush['goodsid']]);
        $assign['goods'] = $goods;
        // 统计参与人数
        $total = $this->db->count('sys_goods_rush', ['pid' => $rushid]);
        // 计算剩余人数
        $rush['surplus'] = $goods['rushnum'] - $total - 1;
        // 剩余时间
        $rush['sec'] = $this->secToTime($rush['regdate'] + 86400 - time());
        $assign['rush'] = $rush;

        // 获取参团列表
        $tuanuser = $this->db->select('sys_goods_rush', '*', ['OR' => ['id' => $rushid, 'pid' => $rushid], 'ORDER' => ['pid' => 'ASC']]);

        foreach ($tuanuser as $key => $val) {
            $avatar = $this->db->get('sys_client', ['avatar'], ['id' => $tuanuser[$key]['uid']]);
            $tuanuser[$key]['avatar'] = $avatar['avatar'];
        }
        $assign['tuanuser'] = $tuanuser;

        echo $this->twig->render('/index/rushDetails.html', $assign);
    }

    public function rushingAction()
    {
        $assign = array(
            'pagetitle' => '正在开团',
        );
        $goodsid = intval(Help::getg('goodsid'));
        if ($goodsid < 1) {
            $this->success(0, '参数错误!');
        }
        //
        $goods = $this->db->get('sys_goods', ['rushnum'], ['id' => $goodsid]);
        //
        $list = $this->db->select('sys_goods_rush', '*', ['goodsid' => $goodsid, 'status' => 1, 'pid' => 0]);

        foreach ($list as $key => $val){
            // 统计参与人数
            $total = $this->db->count('sys_goods_rush', ['pid' => $list[$key]['id']]);

            // 计算剩余人数
            $list[$key]['surplus'] = $goods['rushnum'] - $total - 1;
            // 剩余时间
            $list[$key]['sec'] = $this->secToTime($list[$key]['regdate'] + 86400 - time());
            // 用户头像&昵称
            $user = $this->db->get('sys_client', ['nickname', 'avatar'], ['id' => $list[$key]['uid']]);
            $list[$key]['nickname'] = $user['nickname'];
            $list[$key]['avatar'] = $user['avatar'];
        }
        $assign['list'] = $list;
        $assign['goodsid'] = $goodsid;
        echo $this->twig->render('/index/rushing.html', $assign);
    }
}
