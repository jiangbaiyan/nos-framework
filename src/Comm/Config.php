<?php
/**
 * 配置操作类
 * Created by PhpStorm.
 * User: baiyan
 * Date: 2019-10-18
 * Time: 10:32
 */

namespace Nos\Comm;

use Nos\Exception\CoreException;
use Yaf\Config\Ini;

class Config
{
    /**
     * 获取配置
     * @param string $path 如db.ini
     * @return mixed
     * @throws CoreException
     */
    public static function get(string $path) {
        // 如果配置池中没有当前路径下的配置，需要重新获取，否则直接拿来用
        $poolData = Pool::get($path);
        if (empty($poolData)) {
            $configPath = APP_PATH . "/config/{$path}";
            if (!is_file($configPath)) {
                throw new CoreException("配置文件:{$configPath}不存在");
            }
            $config = new Ini($configPath, ini_get('yaf.environ'));
            $poolData = Pool::set($path, $config->toArray());
        }
        return $poolData;
    }
}