<?php
/**
 * 日志操作类
 * Created by PhpStorm.
 * User: baiyan
 * Date: 2018-11-28
 * Time: 12:05
 */

namespace Nos\Comm;

class Log
{

    /*
     * 日志级别由高到低
     */
    const LEVEL_ERROR  = 'ERROR';
    const LEVEL_NOTICE = 'NOTICE';
    const LEVEL_DEBUG  = 'DEBUG';

    /**
     * 写日志
     * @param $level
     * @param $msg
     * @return bool
     */
    public static function write(string $level, string $msg)
    {
        $dir = APP_PATH . '/logs/';
        // 判断目录是否存在，不存在则创建
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        // 拼接日志完整路径
        $path = $dir . date('Y-m-d') . '.log';
        $str = '[' . date('Y-m-d H:i:s') . ']' . "[{$level}]". $msg . PHP_EOL;
        // 写入文件
        $handle = fopen($path, 'a');
        flock($handle, LOCK_EX|LOCK_NB);
        fwrite($handle, $str);
        flock($handle, LOCK_UN);
        fclose($handle);
        return true;
    }

    /**
     * 严重错误日志
     * @param string $msg
     * @return bool
     */
    public static function error(string $msg)
    {
        return self::write(self::LEVEL_ERROR, $msg);
    }

    /**
     * 一般错误日志
     * @param string $msg
     * @return bool
     */
    public static function notice(string $msg)
    {
        return self::write(self::LEVEL_NOTICE, $msg);
    }

    /**
     * 调试日志
     * @param string $msg
     * @return bool
     */
    public static function debug(string $msg)
    {
        return self::write(self::LEVEL_DEBUG, $msg);
    }

}