<?php
/**
 * 配置操作类
 * Created by PhpStorm.
 * User: baiyan
 * Date: 2019-10-18
 * Time: 10:32
 */

namespace Nos\Comm;

use Yaf\Config\Ini;

class Config
{
    /**
     * key为文件路径
     * value为配置数据
     * @var array 配置池
     */
    private static $configPool = [];

    /**
     * 获取配置
     * @param string $path 如db.ini
     * @return mixed
     */
    public static function get(string $path) {
        // 如果配置池中没有当前路径下的配置，需要重新获取
        if (!isset(self::$configPool[$path])) {
            $config = new Ini(APP_PATH . "/config/{$path}", ini_get('yaf.environ'));
            self::$configPool[$path] = $config->toArray();
        }
        return self::$configPool[$path];
    }
}