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
        foreach ($rules as $field => $rule) {
            if (!isset($params[$field]) && strpos($rule, 'required') === false) {
                continue;
            }
            // required规则特殊处理
            if (!isset($params[$field]) && strpos($rule, 'required') !== false ) {
                throw new ParamValidateFailedException("参数:{$field}必填");
            }
            // 处理其他规则
            $ruleArr = explode('|', $rule);
            foreach ($ruleArr as $ruleItem) {
                // 验证规则为空，进行下一条验证
                if (empty($ruleItem) || $ruleItem == 'required') {
                    continue;
                }
                // 如果在本类定义了内置验证方法，直接调用。如果没定义，那么继续尝试is_int/is_numeric等PHP内置方法，否则报错
                if (method_exists(__CLASS__, $ruleItem)) { // 首先检测本类中是否定义验证规则
                    $ret = call_user_func([__CLASS__, $ruleItem], $params[$field]);
                } else if (function_exists("is_{$ruleItem}")) { // 李勇PHP内置函数检测integer等规则
                    $ret = call_user_func("is_{$ruleItem}", $params[$field]);
                } else if (strpos($ruleItem, ':')) { // minNum/minLen/betweenNum/in/exist等情况
                    // 根据冒号分隔
                    list($key, $value) = explode(':', $ruleItem);
                    // 查找本类有没有规则对应的方法
                    if (!method_exists(__CLASS__, $key)) {
                        throw new CoreException("validator|rule_undefined|rule:{$ruleItem}");
                    }
                    // 方法调用。传入请求参数传来的值和给定比较的值
                    $ret = call_user_func([__CLASS__, $key], $params[$field], $value);
                } else { // 以上三种情况均没有，报错
                    throw new CoreException("validator|rule_undefined|rule:{$ruleItem}");
                }
                if ($ret === false) { // 校验失败收口统一报错
                    throw new ParamValidateFailedException("validator|field:{$field}:{$params[$field]}_not_comply_with_rule:{$ruleItem}");
                }
            }
        }
        return true;
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

    /**
     * 数值是否比目标小
     * @param $paramVal
     * @param $objVal
     * @return bool
     */
    private static function maxNum($paramVal, $objVal)
    {
        return $paramVal <= $objVal;
    }

    /**
     * 数值是否比目标大
     * @param $paramVal
     * @param $objVal
     * @return bool
     */
    private static function minNum($paramVal, $objVal)
    {
        return $paramVal >= $objVal;
    }

    /**
     * 字符串长度是否比目标小
     * @param $paramVal
     * @param $objVal
     * @return bool
     */
    private static function maxLen($paramVal, $objVal)
    {
        return strlen($paramVal) <= $objVal;
    }

    /**
     * 字符串长度是否比目标大
     * @param $paramVal
     * @param $objVal
     * @return bool
     */
    private static function minLen($paramVal, $objVal)
    {
        return strlen($paramVal) >= $objVal;
    }

    /**
     * 数值是否处于两个值之间
     * @param $paramVal
     * @param $objVal
     * @return bool
     */
    private static function betweenNum($paramVal, $objVal)
    {
        list($num1, $num2) = explode(',', $objVal);
        return ($paramVal >= $num1 && $paramVal <= $num2);
    }

    /**
     * 字符串长度是否处于两个值之间
     * @param $paramVal
     * @param $objVal
     * @return bool
     */
    private static function betweenLen($paramVal, $objVal)
    {
        list($num1, $num2) = explode(',', $objVal);
        $len = strlen($paramVal);
        return ($len >= $num1 && $len <= $num2);
    }

    /**
     * 判断参数值是否在给定的枚举值中
     * @param $paramVal
     * @param $objVal
     * @return bool
     */
    private static function in($paramVal, $objVal)
    {
        return in_array($paramVal, explode(',', $objVal));
    }

    /**
     * 检查参数字符串是否存在给定的子串
     * @param $paramVal
     * @param $objVal
     * @return false|int
     */
    private static function exist($paramVal, $objVal)
    {
        return strpos($paramVal, $objVal);
    }
}
