<?php
/**
 * 请求操作类
 * Created by PhpStorm.
 * User: baiyan
 * Date: 2018-12-02
 * Time: 11:31
 */

namespace Nos\Http;

use Nos\Comm\Config;
use Nos\Comm\File;
use Nos\Exception\CoreException;
use Yaf\Config\Ini;

class Request
{

    const PARAMS_TYPE_URLENCODED = 1; // QUERY_STRING形式提交参数
    const PARAMS_TYPE_FORM_DATA  = 2; // 浏览器表单形式提交参数
    const PARAMS_TYPE_JSON       = 3; // 请求体携带JSON形式提交参数

    const REQUEST_TYPE_GET       = 'GET'; // GET请求
    const REQUEST_TYPE_POST      = 'POST'; // POST请求
    const REQUEST_TYPE_PUT       = 'PUT'; // PUT请求
    const REQUEST_TYPE_DELETE    = 'DELETE'; // DELETE请求


    /**
     * 获取单个GET参数
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function get(string $key, string $default = '')
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * 获取单个POST参数
     * @param string $key
     * @param string $default
     * @return mixed|string
     */
    public static function post(string $key, string $default = '')
    {
        return $_POST[$key] ?? $default;
    }


    /**
     * 获取文件信息
     * 返回数据示例：
     * [
     *     "name": "WechatIMG9.jpeg",
     *     "type": "image/jpeg",
     *     "tmp_name": "/tmp/phpSMXprN",
     *     "error": 0,
     *     "size": 25569
     * ]
     * @param $key
     * @return File|bool
     */
    public static function file(string $key)
    {
        if (!isset($_FILES[$key]) || empty($_FILES[$key])) {
            return false;
        }
        return new File($_FILES[$key]);
    }

    /**
     * 获取所有请求参数
     * @return array|mixed
     */
    public static function all()
    {
        // 从输入流中获取所有参数（包括PUT/DELETE）
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        // 所有参数合并返回
        return array_merge($_GET, $_POST, $data);
    }

    /**
     * 获取请求头
     * @param string $key
     * @param string $default
     * @return array|false|mixed|null
     */
    public static function header(string $key = '', string $default = '')
    {
        $headers = [];
        if (!function_exists('getallheaders')) {
            function getallheaders() {
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }
            }
        } else{
            $headers = getallheaders();
        }
        if (empty($key)){
            return $headers;
        }
        return isset($headers[$key]) ? $headers[$key] : $default;
    }


    /**
     * 获取完整URL
     * @return string
     */
    public static function getFullUrl()
    {
        // 缓存协议为空，需要重新从配置文件获取
        $config = Config::get('application.ini');
        return $config['schema'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }


    /**
     * 获取不带参数的url
     * @return string
     */
    public static function getBaseUrl()
    {
        $fullUrl = self::getFullUrl();
        if (strpos($fullUrl, '?') === false){
            return $fullUrl;
        }
        $arr = explode('?', $fullUrl);
        return $arr[0];
    }


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
    public static function send(string $reqType, string $url, array $params = [], int $paramsType = self::REQUEST_TYPE_URLENCODED,  array $options = [], int $retry = 3, int $timeout = 20)
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
            self::PARAMS_TYPE_FORM_DATA,
            self::PARAMS_TYPE_JSON
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
            // 判断请求参数携带类型并设置请求参数
            switch ($paramsType) {
                case self::PARAMS_TYPE_URLENCODED:
                    $params = http_build_query($params);
                    break;
                case self::PARAMS_TYPE_FORM_DATA:
                    break;
                case self::PARAMS_TYPE_JSON:
                    $params = json_encode($params);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Content-Type: application/json; charset=utf-8',
                            'Content-Length: ' . strlen($params)
                    ]);
                    break;
            }
            // 判断并设置请求类型
            switch ($reqType) {
                case 'POST':
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                    break;
                case 'PUT':
                case 'DELETE':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $reqType);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                    break;
            }
            // 发送请求
            $res = curl_exec($ch);
            if (empty($res)){
                for ($i = 0;$i<$retry;$i++){
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
        } catch (\Exception $e) {
            throw new CoreException('curl|send_request_error|url:' . $url . '|reqType:' . $reqType . '|paramsType:' . $paramsType . '|$params:' . json_encode($params) . '|retry:' . $retry . '|curl_exception:' . $e->getMessage() . '|curl_error:' . curl_error($ch));
        }
    }

}