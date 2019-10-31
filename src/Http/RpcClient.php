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


    public function __construct($serviceName)
    {
        $config = Config::get('rpc.ini');
        if (!isset($config[$serviceName])) {
            throw new CoreException('rpc|ini_is_empty');
        }
        $this->serviceUrl  = $config[$serviceName]['host'];
        $this->appId       = $config[$serviceName]['appId'];
        $this->accessToken = $config[$serviceName]['accessToken'];
        $this->serviceName = $serviceName;
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