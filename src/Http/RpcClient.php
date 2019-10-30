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
     * rpc单个调用
     * @param $actionName
     * @param $params
     * @param $type
     * 实例： $actionName = 'article/query'
     *       $params = [ 'id' => 1]
     *       $type = 'post';
     * @return bool|string
     * @throws CoreException
     */
    public function send($actionName, $params, $type)
    {
        $params['appId'] = $this->appId;
        $params['accessToken'] = $this->accessToken;
        $params['timestamp'] = time();
        $url = $this->serviceUrl . '/' . $actionName;
        $res = Request::send($type, $url, $params);
        return $res;
    }


    /**
     * rpc并行调用
     * @param $connomains
     * @param int $post
     * 实例：
     *      $connomains = [
    [
    'path'   => '/unified/register',
    'params' => [ 'id' => 1]
    ]
    ];
     * @return array
     * @throws CoreException
     */
    public function sendMulti($connomains, $post = 0)
    {
        foreach ($connomains as $k => $val) {
            $connomains[$k]['params']['appId'] = $this->appId;
            $connomains[$k]['params']['accessToken'] = $this->accessToken;
            $connomains[$k]['params']['timestamp'] = time();
        }
        if (empty($connomains)) {
            throw new CoreException('rpc|rpc_send_multi: params is empty');
        }
        return Request::sendMulti($connomains, $this->serviceUrl, $post);
    }
}