<?php
/**
 * RPC客户端
 * Created by PhpStorm.
 * User: nos
 * Date: 2019-10-31
 * Time: 10:31
 */

namespace Nos\Http;

use Nos\Comm\Pool;
use Nos\Exception\CoreException;
use Nos\Comm\Config;

class RpcClient
{

    /**
     * 服务host必填
     * @var string
     */
    private $host = '';


    /**
     * 单例获取RPC实例
     * @param string $serviceName
     * @return array|mixed|RpcClient
     * @throws CoreException
     */
    public static function getInstance(string $serviceName)
    {
        $rpcInstance = Pool::get($serviceName);
        if (empty($rpcInstance)) {
            $config = Config::get('rpc.ini');
            if (!isset($config[$serviceName])) {
                throw new CoreException('rpc|rpc_ini_is_empty');
            }
            $rpcInstance = new self();
            $rpcParams = $config[$serviceName];
            // host/appId/accessToken等配置信息赋值，取决于配置文件字段名
            foreach ($rpcParams as $key => $value) {
                $rpcInstance->$key = $value;
            }
            Pool::set($serviceName, $rpcInstance);
        }
        return $rpcInstance;
    }


    /**
     * RPC调用
     * @param string $reqType 请求类型
     * @param string $actionName 动作名称
     * @param array $params 请求参数
     * @return bool|string
     * @throws CoreException
     */
    public function send(string $reqType, string $actionName, array $params = [])
    {
        // 将刚才获取的配置文件对象中的参数属性添加到请求参数中
        foreach ($this as $property => $value) {
            $params[$property] = $value;
        }
        $url = $this->host . '/' . $actionName;
        $res = ApiClient::send($reqType, $url, $params);
        // 假设RPC通信返回的是json
        if (!empty($res) && is_string($res)) {
            return json_decode($res, true);
        } else {
            return $res;
        }
    }

}