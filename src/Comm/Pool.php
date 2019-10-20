<?php
/**
 * 缓存池类
 * Created by PhpStorm.
 * User: baiyan
 * Date: 2019-10-20
 * Time: 11:21
 */

namespace Nos\Comm;

class Pool
{

    /**
     * 公共缓存池
     * 结构如下：[类名 => [$key => $value]]
     * [
     *     'Db' => [
     *         $key => $value
     *     ],
     *     'Request' => [
     *         $key => $value
     *     ]
     * ]
     * @var array $pool
     */
    private static $pool = [];


    /**
     * 获取缓存池中的数据
     * @param string $key 键
     * @return array|mixed 缓存池数据
     */
    public static function get(string $key)
    {
        $className = get_called_class();
        return self::$pool[$className][$key] ?? [];
    }

    /**
     * 往缓存池添加数据
     * @param string $key 新键
     * @param mixed $value 新值
     * @return mixed $value 新值
     */
    public static function set(string $key, $value)
    {
        $className = get_called_class();
        self::$pool[$className][$key] = $value;
        return $value;
    }
}