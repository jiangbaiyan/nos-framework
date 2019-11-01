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
use Nos\Comm\Pool;

class Request
{


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
        $allParams = Pool::get('request_all');
        if (empty($allParams)) {
            // 从输入流中获取所有参数（包括PUT/DELETE）
            $allParams = json_decode(file_get_contents('php://input'), true) ?? [];
            // 所有参数合并返回
            $allParams = array_merge($_GET, $_POST, $allParams);
            Pool::set('request_all', $allParams);
        }
        return $allParams;
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

}