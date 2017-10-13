<?php

/**
 * Created by PhpStorm.
 * User: wangjin
 * Date: 2017/9/29
 * Time: 上午9:30
 */
class SearchController extends BaseController
{
    /**
     * 搜索
     */
    public function indexAction()
    {
        $assign = array(
            'pagetitle' => '新品发布',
        );
        // 获取分类列表
        $searchkey = $this->db->select('sys_searchkey', ['id', 'title'], ['ORDER' => ['px']]);
        $assign['searchkey'] = $searchkey;
        echo $this->twig->render('/search/index.html', $assign);
    }
}