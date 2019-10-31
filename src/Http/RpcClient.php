<?php

namespace Nos\Http;

use Nos\Comm\Pool;
use Nos\Exception\CoreException;
use Nos\Comm\Config;

class RpcClient
{

    private $serviceUrl;

    private $serviceName;

    private $appId;

    private $accessToken;


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
                throw new CoreException('rpc|ini_is_empty');
            }
            $rpcInstance = new self();
            $rpcInstance->serviceUrl  = $config[$serviceName]['host'];
            $rpcInstance->appId       = $config[$serviceName]['appId'];
            $rpcInstance->accessToken = $config[$serviceName]['accessToken'];
            $rpcInstance->serviceName = $serviceName;
            Pool::set($serviceName, $rpcInstance);
        }
        return $rpcInstance;
    }


    /**
     * RPC调用
     * @param $actionName
     * @param $params
     * @param $reqType
     * @return bool|string
     * @throws CoreException
     */
    public function send(string $reqType, string $actionName, array $params)
    {
        $params['appId'] = $this->appId;
        $params['accessToken'] = $this->accessToken;
        $params['timestamp'] = time();
        $url = $this->serviceUrl . '/' . $actionName;
        return Request::send($reqType, $url, $params);
    }

}