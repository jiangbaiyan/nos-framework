<?php

namespace Nos\Comm;

class Apiclient
{
    private $serviceUrl;
    private $serviceName;
    protected static $rpcConfig = [
        'UserService' => 'http://152.136.125.67:9600',
    ];

    public function __construct($serviceName)
    {
        if (array_key_exists($serviceName, static::$rpcConfig)) {
            $this->serviceUrl = static::$rpcConfig[$serviceName];
            $this->serviceName = $serviceName;
        }
    }

    public function callApi($actionName, $arguments)
    {
        $content = json_encode($arguments);
        $options['http'] = [
            'timeout' => 5,
            'method' => 'POST',
            'header' => 'Content-type:applicaion/x-www-form-urlencoode',
            'content' => $content,
        ];
        //创建资源流上下文
        $context = stream_context_create($options);

        $get = [
            'service_name' => $this->serviceName,
            'action_name' => $actionName
        ];

        $serviceUrl = $this->serviceUrl  . $get['action_name'];
        $result = file_get_contents($serviceUrl, false, $context);
        return json_decode($result, true);
    }

    public function curlApi($actionName, $params, $post = 0)
    {
        $curl = curl_init();
        //设置抓取的url
        $serviceUrl = $this->serviceUrl  . $actionName;

        if ($post === 0) {
            if (!empty($params)) {
                $serviceUrl = $serviceUrl . '?';
                foreach ($params as $k => $val) {
                    $serviceUrl = $serviceUrl . $k . '=' . $val . '&';
                }
            }
            $serviceUrl = trim($serviceUrl, '&');
        }
        curl_setopt($curl, CURLOPT_URL, $serviceUrl);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, false);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if ($post === 1) {
            //设置post方式提交
            curl_setopt($curl, CURLOPT_POST, 1);
            //设置post数据
            $post_data = $params;
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        }

        $data = curl_exec($curl);

        $data = json_decode($data, true);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return $data;
    }


    public function curlApiMulti($connomains,$post = 0)
    {
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
            }else {
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

        return !empty($res) ? $res : [];
    }
}