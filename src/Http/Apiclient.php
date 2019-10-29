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
        try {
            $handler = curl_multi_init();
            foreach ($connomains as $i => $value) {
                $conn[$i] = curl_init($this->serviceUrl . '/' . $value['path']);
                if ($post === 0 && !empty($value['params'])) {
                    $str = '';
                    foreach ($value['params'] as $k => $val) {
                        $str = $str . $k . '=' . $val . '&';
                    }
                    $str = trim($str, '&');
                    $conn[$i] = curl_init($this->serviceUrl . '/' . $value['path'] . '?' . $str);
                } else {
                    $conn[$i] = curl_init($this->serviceUrl . '/' . $value['path']);
                }
                curl_setopt($conn[$i], CURLOPT_RETURNTRANSFER, 1);
                if ($post === 1) {
                    curl_setopt($conn[$i], CURLOPT_POST, 1);
                    curl_setopt($conn[$i], CURLOPT_POSTFIELDS, $value['params']);
                }

                curl_multi_add_handle($handler, $conn[$i]);
            }

            $active = null;
            do {
                $result = curl_multi_exec($handler, $active);
            } while ($result == CURLM_CALL_MULTI_PERFORM);
            while ($active and $result == CURLM_OK) {
                if (curl_multi_select($handler) != -1) {
                    do {
                        $result = curl_multi_exec($handler, $active);
                    } while ($result == CURLM_CALL_MULTI_PERFORM);
                }
            }

            foreach ($connomains as $i => $url) {

                //获取curl获取到的内容
                $res[$i] = curl_multi_getcontent($conn[$i]);

                curl_close($conn[$i]);

                curl_multi_remove_handle($handler, $conn[$i]);
            }
            curl_multi_close($handler);
        }catch (\Exception $e) {
            throw new CoreException('curl|apic_lient_request_error|paramsType:' . $post . '|$params:' . json_encode($connomains) . '|curl_exception:' . $e->getMessage() . '|curl_error:' . curl_error($handler));
        }

        return !empty($res) ? $res : [];
    }
}