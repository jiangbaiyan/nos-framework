<?php

namespace Nos\Http;

use Nos\Exception\CoreException;
use Nos\Comm\Config;

class RpcClient
{
    private $serviceUrl;
    private $serviceName;
    private $appId;
    private $accessToken;

    protected  $rpcConfig = '';

    public function __construct($serviceName)
    {
        $this->rpcConfig = Config::get('rpc.ini');
        if (array_key_exists($serviceName, $this->rpcConfig)) {
            $this->serviceUrl = $this->rpcConfig[$serviceName]['host'];
            $this->appId = $this->rpcConfig[$serviceName]['appId'];
            $this->accessToken = $this->rpcConfig[$serviceName]['accessToken'];
            $this->serviceName = $serviceName;
        } else {
            throw new CoreException('rpc|ini_is_empty');
        }
    }


    /**
     * rpc调用
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