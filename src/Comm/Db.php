<?php
/**
 * 数据库操作类
 * Created by PhpStorm.
 * User: baiyan
 * Date: 2018-11-30
 * Time: 16:32
 */

namespace Nos\Comm;

use Nos\Exception\CoreException;
use PDO;

class Db
{

    const DB_NODE_MASTER_KEY = 'write'; //主库
    const DB_NODE_SLAVE_KEY  = 'read';  //从库


    /**
     * 执行原生sql语句
     * @param string $node 数据库节点
     * @param string $sql SQL语句
     * @param array $bind 参数绑定
     * @param bool $lastInsertId 是否返回执行SQL后的记录id
     * @return mixed
     * @throws CoreException
     */
    public static function doSql(string $node, string $sql, array $bind = [], bool $lastInsertId = false)
    {
        try{
            // 从连接池拿出数据库实例
            $dbInstance = self::getInstance($node);
            $dbInstance->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $handle = $dbInstance->prepare($sql);
            if (!$handle) {
                $errorInfo = $dbInstance->errorInfo();
                throw new CoreException("SQLSTATE[{$errorInfo[0]}][$errorInfo[1]] {$errorInfo[2]}");
            }
            $res = $handle->execute($bind);
            if (!$res){
                $errorInfo = $dbInstance->errorInfo();
                throw new CoreException("SQLSTATE[{$errorInfo[0]}][$errorInfo[1]] {$errorInfo[2]}");
            }
            if ($lastInsertId) {
                return $dbInstance->lastInsertId();
            }
            // 获取影响行数
            $count = $handle->rowcount();
            // 主库增删改操作，直接返回影响的行数
            if ($node == self::DB_NODE_MASTER_KEY){
                return $count;
            }
            // 以下是查询操作
            if (!$count){
                return []; // 结果集为空，直接返回
            } else {
                return $handle->fetchAll(PDO::FETCH_ASSOC); // 结果集不为空，取出数据
            }
        } catch (\Throwable $e){
            throw new CoreException('db|pdo_do_sql_failed|msg:' .  $e->getMessage() . '|sql:' . $sql . '|node:' . $node . '|bind:' . json_encode($bind));
        }
    }

    /**
     * 从连接池获取数据库连接实例
     * @param string $node 节点类型
     * @return mixed
     * @throws CoreException
     */
    public static function getInstance(string $node)
    {
        try {
            /* 获取数据库配置，返回的配置格式如下:
             * [
             *     write => [],
             *     read => []
             * ]
             */
            $config = Config::get('db.ini');
            // 获取当前节点下的配置
            $config = $config[$node];
            // 子类指定数据库
            if (!empty(static::$database)) {
                $config['database'] = static::$database;
            }
            // PDO连接
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8";
            // 这几个参数唯一确定连接池的key
            $connPoolKey = $dsn . $config['user'] . $config['password'];
            // 连接池中不存在，需要重新往连接池中添加
            $dbInstance = Pool::get($connPoolKey);
            if (empty($dbInstance)) {
                $dbInstance = new PDO($dsn, $config['user'], $config['password']);
                Pool::set($connPoolKey, $dbInstance);
            }
            return $dbInstance;
        } catch (\Throwable $e) {
            throw new CoreException('db|connect_failed|msg:' . $e->getMessage() . '|config:' . json_encode($config));
        }
    }

    /**
     * 事务开始
     * @throws CoreException
     */
    public static function beginTransaction()
    {
        $dbInstance = self::getInstance(self::DB_NODE_MASTER_KEY);
        $dbInstance->beginTransaction();
    }

    /**
     * 事务提交
     * @throws CoreException
     */
    public static function commit()
    {
        $dbInstance = self::getInstance(self::DB_NODE_MASTER_KEY);
        $dbInstance->commit();
    }

    /**
     * 事务回滚
     * @throws CoreException
     */
    public static function rollback()
    {
        $dbInstance = self::getInstance(self::DB_NODE_MASTER_KEY);
        $dbInstance->rollback();
    }
}
