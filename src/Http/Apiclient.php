<?php

namespace Nos\Comm;

use Nos\Http\Request;
use Nos\Exception\CoreException;

class Apiclient
{
    private $serviceUrl;
    private $serviceName;
    protected  $rpcConfig = '';

    public function __construct($serviceName)
    {
        $this->rpcConfig = Config::get('rpc.ini');
        if (array_key_exists($serviceName, $this->rpcConfig)) {
            $this->serviceUrl = $this->rpcConfig[$serviceName]['host'];
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
    public function curlApiMulti($connomains,$post = 0)
    {
        if (empty($connomains)) {
            throw new CoreException('curl|curlApiMulti: params is empty');
        }

        return Request::sendMulti($connomains, $this->serviceUrl, $post);
    }
}