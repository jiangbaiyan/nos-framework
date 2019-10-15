<?php
/**
 * 常用参数验证类，可自行新建方法自定义验证规则，注意方法名要和验证规则名称一致
 * Created by PhpStorm.
 * User: baiyan
 * Date: 2018-11-28
 * Time: 16:57
 */

namespace Nos\Comm;

use Nos\Exception\CoreException;
use Nos\Exception\ParamValidateFailedException;

class Validator
{

    /**
     * 请求参数校验入口方法
     * @param array $params
     * @param array $rules
     * @return bool
     * @throws CoreException
     * @throws ParamValidateFailedException
     */
    public static function make(array $params, array $rules)
    {
        foreach ($rules as $k => $v){
            if (!isset($params[$k]) && strpos($v, 'required') === false){
                continue;
            }
            $arr = explode('|', $v);
            foreach ($arr as $item){
                if (empty($item)){
                    throw new CoreException('validator|rule_is_empty');
                }
                if (!method_exists(__CLASS__, $item)){
                    throw new CoreException('validator|rule_not_defined|rule:' . $item);
                }
                if (@call_user_func([__CLASS__, $item], $params[$k]) === false) {
                    throw new ParamValidateFailedException("参数{$params[$k]}不符合校验规则{$item}");
                };
            }
        }
        return true;
    }

    /**
     * 手机号验证
     * @param $v
     * @return bool
     */
    private static function phone($v)
    {
        return (strlen($v) == 11 && preg_match('/^[1][3,4,5,7,8][0-9]{9}$/', $v));
    }

    /**
     * 邮箱验证
     * @param $v
     * @return false|int
     */
    private static function email($v)
    {
        return preg_match('/^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,4}$/', $v);
    }

    /**
     * 身份证验证
     * @param $v
     * @return false|int
     */
    private static function idCard($v)
    {
        return preg_match('/(^([\d]{15}|[\d]{18}|[\d]{17}x)$)/', $v);
    }

    /**
     * 必填验证
     * @param $v
     * @return bool
     */
    private static function required($v)
    {
        return isset($v);
    }

    /**
     * 判断日期时间格式是否合法
     * @param $v
     * @return bool
     */
    private static function dateTime($v)
    {
        return strtotime(date('Y-m-d H:i:s', strtotime($v))) == strtotime($v);
    }

    /**
     * 判断日期格式是否合法
     * @param $v
     * @return bool
     */
    private static function date($v)
    {
        return strtotime(date('Y-m-d H:i:s', strtotime($v))) == strtotime($v);
    }
}