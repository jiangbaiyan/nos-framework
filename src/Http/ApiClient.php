<?php
/**
 * 接口调用客户端类
 * Created by PhpStorm.
 * User: baiyan
 * Date: 2019-11-1
 * Time: 09:31
 */

namespace Nos\Http;

use Nos\Exception\CoreException;

class ApiClient
{

    const PARAMS_TYPE_URLENCODED = 1; // QUERY_STRING形式提交参数
    const PARAMS_TYPE_JSON       = 2; // 请求体携带JSON形式提交参数
    const PARAMS_TYPE_COMMON     = 3; // 通用形式提交参数（参数不作处理）


    const REQUEST_TYPE_GET       = 'GET';    // GET请求
    const REQUEST_TYPE_POST      = 'POST';   // POST请求
    const REQUEST_TYPE_PUT       = 'PUT';    // PUT请求
    const REQUEST_TYPE_DELETE    = 'DELETE'; // DELETE请求


    /**
     * 串行阻塞发送Http请求，支持GET/POST/PUT/DELETE
     * @param string $reqType 请求类型
     * @param string $url 请求URL
     * @param array $params 请求体携带数据
     * @param int $paramsType 请求参数携带类型
     * @param array $options 额外CURL选项
     * @param int $retry 重试次数
     * @param int $timeout 超时时间
     * @return bool|string
     * @throws CoreException
     */
    public static function send(string $reqType, string $url, array $params = [], int $paramsType = self::PARAMS_TYPE_URLENCODED,  array $options = [], int $retry = 3, int $timeout = 20)
    {
        if (empty($reqType) || empty($url)) {
            return false;
        }
        $reqType = strtoupper($reqType);
        // 是否是合法请求类型
        if (!in_array($reqType, [
            self::REQUEST_TYPE_GET,
            self::REQUEST_TYPE_POST,
            self::REQUEST_TYPE_PUT,
            self::REQUEST_TYPE_DELETE
        ])) {
            throw new CoreException('curl|wrong_request_type|url:' . $url . '|reqType:' . $reqType . '|paramsType:' . $paramsType .  '|$params:' .json_encode($params));
        }
        // 是否是合法请求参数类型
        if (!in_array($paramsType, [
            self::PARAMS_TYPE_URLENCODED,
            self::PARAMS_TYPE_JSON,
            self::PARAMS_TYPE_COMMON
        ])) {
            throw new CoreException('curl|wrong_params_type|url:' . $url . '|reqType:' . $reqType . '|paramsType:' . $paramsType . '|$params:' .json_encode($params));
        }
        try {
            $ch = curl_init($url);
            // 初始化选项
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT        => $timeout
            ]);
            // 加载额外选项
            if (!empty($options)){
                curl_setopt_array($ch, $options);
            }
            $strQuery = '';
            // 判断请求参数携带类型并设置请求参数
            if (!empty($params)) {
                switch ($paramsType) {
                    case self::PARAMS_TYPE_URLENCODED:
                        $strQuery = '?';
                        foreach ($params as $strKey => $strValue) {
                            $strQuery .= $strKey . '=' . $strValue . '&';
                        }
                        $strQuery = rtrim($strQuery, '&');
                        break;
                    case self::PARAMS_TYPE_JSON:
                        $params = json_encode($params);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Content-Type: application/json; charset=utf-8',
                            'Content-Length: ' . strlen($params)
                        ]);
                        break;
                    case self::PARAMS_TYPE_COMMON:
                        break;
                }
            }
            // 判断并设置请求类型
            switch ($reqType) {
                case self::REQUEST_TYPE_GET:
                    $url .= $strQuery;
                    curl_setopt($ch, CURLOPT_URL, $url);
                    break;
                case self::REQUEST_TYPE_POST:
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                    break;
                case self::REQUEST_TYPE_PUT:
                case self::REQUEST_TYPE_DELETE:
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $reqType);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                    break;
            }
            // 发送请求，重试3次
            $res = curl_exec($ch);
            if (empty($res)){
                for ($i = 0; $i < $retry; $i++){
                    $res = curl_exec($ch);
                    if (!empty($res)){
                        break;
                    }
                }
                if ($i == $retry){
                    throw new CoreException('curl|send_request_error|url:' . $url . '|reqType:' . $reqType . '|paramsType:' . $paramsType . '|$params:' .json_encode($params) . '|retry:' . $retry . '|curl_error:' . curl_error($ch));
                }
            }
            curl_close($ch);
            return $res;
        } catch (\Throwable $e) {
            throw new CoreException('curl|send_request_error|url:' . $url . '|reqType:' . $reqType . '|paramsType:' . $paramsType . '|$params:' . json_encode($params) . '|retry:' . $retry . '|curl_exception:' . $e->getMessage() . '|curl_error:' . curl_error($ch));
        }
    }


    /**
     * 并行请求接口
     * 实例：
     * $data = [
     *     'appId' => 'uc_all',
     *     'accessToken' => 111,
     *     'timestamp'   => 111,
     *     'email' => "123122133@qq.com",
     *     'password' => "123www"
     * ];
     *
     * $requestParams = [
     *     [
     *         'url'   => 'http://baidu.com/unified/register',
     *         'params' => $data
     *     ]
     * ];
     * $serviceUrl = "http://www.baidu.com";
     * @param array $params
     * @param string $url
     * @param string $reqType
     * @return array
     * @throws CoreException
     */
    public static function sendMulti(string $reqType, string $url, array $params)
    {
        try {
            $chs = [];
            $res = [];
            $handler = curl_multi_init();
            foreach ($params as $nIndex => $value) {
                $chs[$nIndex] = curl_init();
                curl_setopt($chs[$nIndex], CURLOPT_RETURNTRANSFER, 1);
                switch ($reqType) {
                    case self::REQUEST_TYPE_GET:
                        $strQuery = '?';
                        foreach ($params as $strKey => $strValue) {
                            $strQuery .= $strKey . '=' . $strValue . '&';
                        }
                        $strQuery = rtrim($strQuery, '&');
                        $url .= $strQuery;
                        break;
                    case self::REQUEST_TYPE_POST:
                        curl_setopt($chs[$nIndex], CURLOPT_POST, true);
                        curl_setopt($chs[$nIndex], CURLOPT_POSTFIELDS, $value['params']);
                        break;
                    case self::REQUEST_TYPE_PUT:
                    case self::REQUEST_TYPE_DELETE:
                        curl_setopt($chs[$nIndex], CURLOPT_CUSTOMREQUEST, $reqType);
                        curl_setopt($chs[$nIndex], CURLOPT_POSTFIELDS, $value['params']);
                        break;
                }
                curl_setopt($chs[$nIndex], CURLOPT_URL, $url);
                curl_multi_add_handle($handler, $chs[$nIndex]);
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

            foreach ($params as $nIndex => $url) {

                //获取curl获取到的内容
                $res[$nIndex] = curl_multi_getcontent($chs[$nIndex]);

                curl_close($chs[$nIndex]);

                curl_multi_remove_handle($handler, $chs[$nIndex]);
            }

            curl_multi_close($handler);

            return $res;
        } catch (\Throwable $e) {
            throw new CoreException('curl|send_request_error|paramsType:' . $reqType . '|$params:' . json_encode($requestParams) . '|curl_exception:' . $e->getMessage() . '|curl_error:' . curl_error($handler));
        }
    }
}