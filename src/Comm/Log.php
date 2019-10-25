<?php
/**
 * 日志操作类
 * Created by PhpStorm.
 * User: baiyan
 * Date: 2018-11-28
 * Time: 12:05
 */

namespace Nos\Comm;

use Nos\Http\Response;

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
        // 拼接日志完整路径
        $path = APP_PATH . '/logs/' . date('Y-m-d') . '.log';
        $str = '[' . date('Y-m-d H:i:s') . ']' . "[{$level}]". $msg . PHP_EOL;
        // 打开文件
        $handle = fopen($path, 'a');
        if (!$handle) {
            Response::apiCoreError('log|open_log_file_failed');
        }
        // 加锁
        if (!flock($handle, LOCK_EX|LOCK_NB)) {
            Response::apiCoreError('log|lock_log_file_failed');
        }
        // 写日志
        if (!fwrite($handle, $str)) {
            Response::apiCoreError('log|write_log_file_failed');
        }
        // 解锁
        if (!flock($handle, LOCK_UN)) {
            Response::apiCoreError('log|unlock_log_file_failed');
        }
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