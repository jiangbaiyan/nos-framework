<?php

namespace Nos\Http;

use Nos\Exception\CoreException;
use Nos\Comm\Config;

class Apiclient
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
            throw new CoreException('curl|Apiclient: ini is empty');
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
    public function curlApi($actionName, $params, $type)
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
     * @param $requestParams
     * @param int $post
     * 实例：
     *      $requestParams = [
    * [
    * 'path'   => '/unified/register',
    * 'params' => [ 'id' => 1]
    * ]
    * ];
     * @return array
     * @throws CoreException
     */
    public function curlApiMulti($requestParams,$post = 'get')
    {
        foreach ($requestParams as $k => $val) {
            $requestParams[$k]['params']['appId'] = $this->appId;
            $requestParams[$k]['params']['accessToken'] = $this->accessToken;
            $requestParams[$k]['params']['timestamp'] = time();
        }
        if (empty($requestParams)) {
            throw new CoreException('curl|curlApiMulti: params is empty');
        }

        return Request::sendMulti($requestParams, $this->serviceUrl, $post);
    }
}