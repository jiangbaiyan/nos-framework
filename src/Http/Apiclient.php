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
        $this->rpcConfig = Config::get('api.ini');
        if (array_key_exists($serviceName, $this->rpcConfig)) {
            $this->serviceUrl = $this->rpcConfig[$serviceName]['host'];
            $this->serviceName = $serviceName;
        } else {
            throw new CoreException('curl|Apiclient: ini is empty');
        }
    }

    public function curlApi($actionName, $params, $type)
    {
        $url = $this->serviceUrl . '/' . $actionName;
        $res = Request::send($type, $url, $params);
        return $res;
    }


    public function curlApiMulti($connomains,$post = 0)
    {
        if (empty($connomains)) {
            throw new CoreException('curl|curlApiMulti: params is empty');
        }

        return Request::sendMulti($connomains, $this->serviceUrl, $post);
    }
}