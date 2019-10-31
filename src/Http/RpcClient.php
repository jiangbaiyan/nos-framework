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

    private $host;

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
            $rpcParams = $config[$serviceName];
            // host/appId/accessToken等配置信息赋值
            foreach ($rpcParams as $key => $value) {
                $rpcInstance->$key = $value;
            }
            $rpcInstance->serviceName = $serviceName;
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
    public function send(string $reqType, string $actionName, array $params)
    {
        $params['appId'] = $this->appId; // TODO 待优化字段可定制，而非写死的appId等值
        $params['accessToken'] = $this->accessToken;
        $params['timestamp'] = time();
        $url = $this->host . '/' . $actionName;
        return Request::send($reqType, $url, $params);
    }

}