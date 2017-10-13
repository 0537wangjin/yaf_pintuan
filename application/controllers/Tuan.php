<?php

/**
 * Created by PhpStorm.
 * User: wangjin
 * Date: 2017/9/29
 * Time: 上午9:30
 */
class TuanController extends BaseController
{
    /**
     * 拼团
     */
    public function indexAction()
    {
        $assign = array(
            'pagetitle' => '拼团商品',
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
        $assign['rush'] = 1;
        $assign['rush_text'] = '参团';
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
}