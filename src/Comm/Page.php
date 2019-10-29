<?php
/**
 * 分页处理
 * Created by PhpStorm.
 * User: baiyan
 * Date: 2018-12-02
 * Time: 11:21
 */

namespace Nos\Comm;

use Nos\Http\Request;

class Page
{
    /**
     * 生成分页URL数据
     * @param int $count
     * @param int $curPage
     * @param int $pageSize
     * @return array
     */
    public static function paginate(int $count, int $curPage, int $pageSize)
    {
        $baseUrl = Request::getFullUrl();
        if (strpos($baseUrl, 'page=') === false){
            if (strpos($baseUrl, '?') === false){
                $baseUrl .= '?page=1';
            } else{
                $baseUrl .= '&page=1';
            }
        }
        if ($count <= 0 || $curPage <= 0 || $pageSize <= 0 || empty($baseUrl)){
            return [];
        }
        $totalPage =  ceil($count / $pageSize);
        if ($totalPage <= 0){
            $totalPage = 1;
        }
        $pattern = '/page=\d+/';
        $firstPageUrl = preg_replace($pattern, 'page=1', $baseUrl);
        $lastPageUrl = preg_replace($pattern, 'page=' . $totalPage, $baseUrl);
        $nextPageUrl =  $curPage == $totalPage ? '' : preg_replace($pattern, 'page=' .($curPage + 1), $baseUrl);
        $prevPageUrl =  $curPage ==  1 ? '' : preg_replace($pattern, 'page=' .($curPage - 1), $baseUrl);

        return [
            'first_page_url' => $firstPageUrl,
            'last_page_url' => $lastPageUrl,
            'current_page' => $curPage,
            'next_page_url' => $nextPageUrl,
            'prev_page_url' => $prevPageUrl,
            'data_count' => $count,
            'total_page' => $totalPage
        ];
    }

    /**
     * 获取分页参数
     * @param int $page 当前页码
     * @param int $length 每页条数
     * @param bool $asString 是否以SQL字符串形式返回
     * @return array|string
     */
    public static function getPageData(int $page = 0, int $length = 0, bool $asString = true)
    {
        // 参数非法，不做分页
        if ($page < 1 || $length < 1) {
            $offset = 0;
            $length = 0;
        } else {
            $offset = ($page - 1) * $length;
        }
        if ($asString) {
            return " limit {$offset}, {$length} ";
        }
        return [
            'offset' => $offset,
            'rows'   => $length
        ];
    }

}