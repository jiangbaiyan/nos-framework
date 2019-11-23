<?php
/**
 * 模型基类
 * Created by PhpStorm.
 * User: baiyan
 * Date: 2018-12-17
 * Time: 08:35
 */

namespace Nos\Base;

use Nos\Comm\Db;
use Nos\Comm\Page;
use Nos\Exception\CoreException;

class BaseModel extends Db
{

    const NOT_DELETED = 0; // 未被删除
    const DELETED     = 1; // 已被删除


    /**
     * @var string $table 表名
     */
    protected static $table;

    /**
     * @var string $database 数据库名
     */
    protected static $database = '';

    /**
     * @var string $softDeleteField 软删除标记字段
     */
    protected static $softDeleteField = 'is_delete';

    /**
     * @var array 操作符
     */
    private static $operations = [
        '=', '>', '<', '>=', '<=', '!=', 'like', 'in'
    ];



    /**
     * 单条插入
     * @param array $row 插入数据的一维数组
     * $row示例:
     * [
     *     'name' => '苍老师',
     *     'age' => 10
     * ]
     * @return int 插入后的记录id
     * @throws CoreException
     */
    public static function insert(array $row)
    {
        if (empty($row)) {
            return true;
        }
        $fields     = array_keys($row);
        $bindFields = array_map(function ($v) {
            return ':' . $v;
        }, $fields);

        $sql = 'insert into `' . static::$table . '` (`' . implode('`,`', $fields) . '`) values (' . implode(',', $bindFields) . ')';

        return self::doSql(self::DB_NODE_MASTER_KEY, $sql, $row, true);
    }

    /**
     * 批量插入
     * @param array $rows 插入数据的二维数组
     * $rows示例:
     * [
     *     [
     *         'name' =>' 苍老师',
     *         'age' => 10
     *     ],
     *     [
     *         'name' => '苍老师',
     *         'age' => 10
     *     ]
     * ]
     * @return int 影响行数
     * @throws CoreException
     */
    public static function insertBatch(array $rows)
    {
        if (empty($rows)) {
            return true;
        }
        // 取出第一个索引下标作为键示例
        $firstData  = $rows[0];
        $countFirstData = count($firstData);
        $fields     = array_keys($firstData);
        // 拼接PDO占位符
        $bindFields = array_map(function () {
            return '?';
        }, $fields);
        $sql = 'insert into `' . static::$table . '` (`' . implode('`,`', $fields) . '`) values ';
        $bindArr = [];
        // 拼接values
        foreach ($rows as $row) {
            if (count($row) != $countFirstData) {
                throw new CoreException('baseModel|wrong_insert_data_number');
            }
            // 拼接sql
            $sql .= '(' . implode(',', $bindFields) . '),';
            // 按序获取绑定参数
            $bindArr = array_merge(array_values($row), $bindArr);
        }
        // 去除多余逗号
        $sql = rtrim($sql, ',');
        return self::doSql(Db::DB_NODE_MASTER_KEY, $sql, $bindArr);
    }


    /**
     * 删除数据
     * 实例： where:  [
     *                    ['id', '=', 1],
     *                    ['name', '=', '苍井空']
     *               ]
     * @param array $where 查询条件
     * @param bool $forceDelete 是否彻底删除
     * @param bool $withTrashed 是否查询出已经被软删除的字段
     * @return int 影响行数
     * @throws CoreException
     */
    public static function delete(array $where, bool $forceDelete = false, bool $withTrashed = false)
    {
        if (empty($where)) {
            throw new CoreException('baseModel|empty_delete_where');
        }
        // 若为软删除，只需更新is_delete为1即可
        if (!$forceDelete) {
            return self::update([
                static::$softDeleteField => self::DELETED,
            ], $where , $withTrashed);
        } else { // 否则是真的删除，直接干掉
            $sql = 'delete from `' . static::$table . '`';
            $where = self::prepareWhere($where, $withTrashed);
            if (!empty($where['where'])) {
                $sql .= ' where ' . $where['where'];
            }
            return self::doSql(self::DB_NODE_MASTER_KEY, $sql, $where['bind']);
        }
    }

    /**
     * 查询数据
     * 实例：
     *       where:  [
     *                    ['id', '=', 1],
     *                    ['name', '=', '苍井空']
     *               ]
     *       option: ['order' => ['id' => 'asc'],
     *                'group' => 'id',
     *                'page'  => 1,
     *                'length' => 10]
     * @param array $fields 需要查询的字段,默认查询所有的字段
     * @param array $where 查询条件
     * @param array $otherOption page| length | group by | order by 等操作
     * @param bool $withTrashed 是否需要查询出已被软删除的记录，默认不查
     * @return array 数据
     * @throws CoreException
     */
    public static function select(array $fields = [], array $where = [], array $otherOption = [], bool $withTrashed = false)
    {
        $where = self::prepareWhere($where, $withTrashed);
        if (!empty($otherOption)) {
            $optionSql = self::prepareOption($otherOption);
        }
        if (empty($fields)) {
            $fields = ['*'];
        } else {
            $fields = array_unique($fields);
        }
        $fieldStr = '`' . implode('`,`', $fields) . '`';
        $sql = 'select ' . $fieldStr . ' from `' . static::$table . '`';
        $countSql = 'select ' . 'count(*) as count' . ' from `' . static::$table . '`';
        if (!empty($where['where'])) {
            $sql      .= ' where ' . $where['where'];
            $countSql .= ' where ' . $where['where'];
        }
        $count = self::doSql(self::DB_NODE_SLAVE_KEY, $countSql, $where['bind'])[0]['count'];
        if ($count == 0) {
            return [
                'total'  => 0,
                'data'   => []
            ];
        }
        if (!empty($optionSql)) {
            $sql .= ' ' . $optionSql;
        }
        $data  = self::doSql(self::DB_NODE_SLAVE_KEY, $sql, $where['bind']);
        // 如果有分页参数，返回分页参数
        if (!empty($otherOption['page']) && !empty($otherOption['length'])) {
            return [
                'page'   => $otherOption['page'],
                'length' => $otherOption['length'],
                'total'  => $count,
                'data'   => $data
            ];
        } else {
            return [
                'total'  => $count,
                'data'   => $data
            ];
        }
    }


    /**
     * 更新数据
     * 实例： where:  [
     *                    ['id', '=', 1],
     *                    ['name', '=', '苍井空']
     *               ]
     *       params: ['age' => 3]
     * @param array $params 更新的数据
     * @param array $where 被更新的记录
     * @param bool $withTrashed 是否需要已被删除的记录
     * @return int 影响行数
     * @throws CoreException
     */
    public static function update(array $params, array $where, bool $withTrashed = false)
    {
        if (empty($where)) {
            throw new CoreException('baseModel|empty_update_where');
        }
        $where = self::prepareWhere($where, $withTrashed);
        $params = array_unique($params);
        $sql = 'update `' . static::$table . '` set ';
        $bind = [];
        // 组装sql与绑定参数
        foreach ($params as $field => $value) {
            $sql .= $field . ' = ' . '?,';
            $bind[] = $value;
        }
        // 去除末尾多余逗号
        $sql = rtrim($sql, ',');
        // 如果有where，那么拼接where
        if (!empty($where['where'])) {
            $sql .= ' where ' . $where['where'];
        }
        return self::doSql(self::DB_NODE_MASTER_KEY, $sql, array_merge($bind, $where['bind']));
    }


    /**
     *
     * 处理where条件
     * 例子:  where:  [
     *                    ['id', '=', 1],
     *                    ['name', '=', '苍井空']
     *               ]
     * @param array $condition 条件数组
     * @param bool $withTrashed 是否需要已被删除的记录
     * @return array
     * [
     *     'where' => '...'
     *     'bind' => []
     * ]
     * @throws CoreException
     */
    public static function prepareWhere(array $condition, bool $withTrashed = false)
    {
        if (empty($condition) && $withTrashed) {
            return [
                'where' => '',
                'bind' => []
            ];
        }
        $whereStr = '';
        $bind = [];
        foreach ($condition as $nKey => $queryArr) {
            list($field, $op, $value) = $queryArr;
            // 判断运算符是否合法
            if (!in_array($op, self::$operations)) {
                throw new CoreException("baseModel|illegal_op:{$op}");
            }
            // 查询运算符为in，value必须为数组
            if ($op == 'in') {
                if (!is_array($value)) {
                    throw new CoreException("baseModel|in_op_illegal_value");
                }
                $whereStr .= $field . ' in ' . '(';
                // 组装sql与绑定参数
                foreach ($value as $item) {
                    $whereStr .= '?,';
                    $bind[] = $item;
                }
                $whereStr = rtrim($whereStr, ',') . ') and ';
            } else { // 是普通的查询运算符
                // 如果是like运算，需要用%包裹
                if ($op == 'like') {
                    $value = "%$value%";
                }
                $whereStr .= $field . " {$op} " . '?' . ' and ';
                $bind[] = $value;
            }
        }
        // 如果不需要包含已被软删除的记录，需要添加一个查询条件is_delete = 0
        if (!$withTrashed) {
            // 获取软删除字段动态绑定，为了兼容每个表有不同的软删除字段
            $deleteField = static::$softDeleteField;
            $whereStr .= "{$deleteField} = ?";
            $bind[] = self::NOT_DELETED;
        } else {
            $whereStr = rtrim($whereStr, ' and ');
        }
        return [
            'where' => $whereStr,
            'bind'  => $bind,
        ];
    }


    /**
     * 特殊选项处理
     * 例子：
     *       option: ['order' => ['id' => 'asc'],
     *                'group' => 'id',
     *                'page'  => 1,
     *                'length' => 10]
     * @param array $options
     * @return string
     */
    public static function prepareOption(array $options)
    {
        $optionArr = [];
        if (!empty($options['group'])) {
            if (is_array($options['group'])) {
                $group = implode(', ', $options['group']);
            } else {
                $group = $options['group'];
            }
            $optionArr[] = 'group by ' . $group;
        }
        if (!empty($options['order'])) {
            if (is_array($options['order'])) {
                $orders = [];
                foreach ($options['order'] as $sortField => $sort_type) {
                    if ($sortField && $sort_type) {
                        $orders[] = ' ' . $sortField . ' ' . $sort_type . ' ';
                    }
                }
                if ($orders) {
                    $optionArr[] = 'order by ' . implode(', ', $orders);
                }
            } else {
                $optionArr[] = $options['order'];
            }
        }
        if (!empty($options['page']) && !empty($options['length'])) {
            $optionArr[] = Page::getPageData($options['page'], $options['length'], true);
        }
        return implode(' ', $optionArr);
    }
}