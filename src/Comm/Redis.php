<?php
/**
 * Created by PhpStorm.
 * User: guobutao001
 * Date: 2018/12/1
 * Time: 14:00
 */

namespace Nos\Comm;

use Nos\Exception\CoreException;

class Redis
{

    /**
     * 获取redis实例
     * @return \Redis
     * @throws CoreException
     */
    public static function getInstance()
    {
        $redis = Pool::get('default');
        if (empty($redis)) {
            // 创建redis实例
            $redis = new \Redis();
            Pool::set('default', $redis);
        }
        // 读取redis配置
        $config = Config::get('redis.ini');
        // 加载redis配置
        $host = $config['host'];
        $port = $config['port'];
        $password = $config['password'];
        $timeout = $config['timeout'];
        $database = $config['database'];
        // 连接
        $result = $redis->connect($host, $port, $timeout);
        if ($result === false) {
            throw new CoreException('redis|connect_failed|errorInfo:' . json_encode($redis->errorInfo()));
        }
        // 密码配置
        if (!empty($password)) {
            $result = $redis->auth($password);
            if ($result === false) {
                throw new CoreException('redis|auth_failed|errorInfo:' . json_encode($redis->errorInfo()));
            }
        }
        // 数据库选择
        if (!empty($database)) {
            $result = $redis->select($database);
            if ($result === false) {
                throw new CoreException('redis|select_db_failed|errorInfo:' . json_encode($redis->errorInfo()));
            }
        }
        return $redis;
    }
}