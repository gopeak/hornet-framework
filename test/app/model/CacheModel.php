<?php

namespace main\app\model;

use \main\lib\MyRedis;

/**
 * 缓存处理类
 * Class CacheModel
 * @package main\app\model
 */
class CacheModel extends DbModel
{

    /**
     * 缓存配置项 $_config['cache']['data']
     *
     * @var string
     */
    private $config;


    /**
     *
     * PHP Redis 缓存对象
     *
     * @var Myredis
     */
    public $cache;

    /**
     * 当前登录的用户id
     * @var int
     */
    public $uid;

    /**
     * 缓存KEY
     */
    const  DATA_KEY = '';

    /**
     * 缓存过期时间.
     * @var int
     */
    public  $expire = 360000;

    /**
     * 缓存键值前缀，用于生成缓存键.
     * @var string
     */
    public static $cache_key_prefix = '';



    /**
     *  构造函数，读取redis配置,创建redis预连接
     *
     * @param bool $persisten
     *            是否使用持久连接，子类继承时在构造函数中传入
     */
    public function __construct( $uid=0,  $persisten = false )
    {
        $this->uid = $uid;
        parent::__construct( $persisten );
        $this->cache = $this->redisConnect(); // 缓存预连接
        $this->expire = CACHE_EXPIRE;

    }


    /**
     * 实例化Redis封装的对象
     * @return boolean|\main\app\classes\MyRedis
     */
    private function redisConnect( )
    {
        static $_myRedisInstance;

        if (empty($_myRedisInstance )) {

            $_cache_cfg =  getConfigVar('cache')['redis']['data'];
            $_myRedisInstance  = new MyRedis( $_cache_cfg ,  ENABLE_CACHE  );
        }
        return $_myRedisInstance ;
    }

    /**
     * 生成缓存键.
     * @return string 将缓存标识参数和缓存键值前缀用短横线拼接返回.
     */
    public static function createKey()
    {
        return self::$cache_key_prefix . '/' . implode('_', func_get_args());
    }

    /**
     * 使用回调方式生成缓存键.
     * @param $callback 回调函数.
     * @param array $params 回调参数.
     * @return bool|mixed
     */
    public function createKeyByCallback($callback, $params=[])
    {
        if (is_callable($callback)){
            return call_user_func_array($callback, $params);
        }else{
            return false;
        }
    }

    /**
     * 从一个表里查询取得一个字段的值，并关联缓存
     *
     * @param string $table
     *            表名
     * @param string $field
     *            字段名
     * @param string $where
     *            查询条件
     * @param string $key
     *            缓存键名
     * @return mixed 返回查询的字段值，失败返回false
     */
    public function getOneByKey( $table, $field, $where, $key = '')
    {
        // 使用缓存机制
        if (! empty($key)   && ! empty($this->cache)) {
            $memflag = $this->cache->get($key);
            if ($memflag !== false) {
                return $memflag;
            }
        }
        // 从数据库中获取,@todo 判断字段如果为boolean类型时的处理
        $one = parent::getOne($table, $field, $where);
        if ( $one && ! empty($key)   && ! empty($this->cache)) {
            $this->cache->set( $key, $one, $this->expire );
        }
        return $one;
    }

    /**
     * 从一个表里查询出数据，并更新缓存
     *
     * @param string $table
     *            表名
     * @param string $fields
     *            字段表
     * @param string $where
     *            查询条件
     * @param string $key
     *            缓存键名
     * @return array 返回查询的数据集,失败返回false
     */
    public function getRowByKey( $table, $fields, $where , $key = '')
    {
        // 如果参数key不为空，则根据key从缓存里取数据，如果没有取到，则到数据库里取，并保存到缓存里
        if (! empty($key)   && ! empty($this->cache)) {
            $memflag = $this->cache->get( $key );
            if ($memflag !== false) {
                return $memflag;
            }
        }
        $row = parent::getRow( $table, $fields, $where );
        if ($row && ! empty($key)   && ! empty($this->cache)) {
            $this->cache->set($key, $row, $this->expire );
        }
        return $row;
    }

    /**
     * 从一个表里查询出数据
     *
     * @param string $table
     *            表名
     * @param string $fields
     *            字段表
     * @param string $where
     *            查询条件
     * @param boolean $primkey
     *            是否返回以数据表主键为键名的二维数组
     * @param string $key
     *            缓存键名
     * @return array 返回查询的数据集，失败返回false
     */
    public function getRowsByKey( $table, $fields, $where, $append=null, $sort = null, $limit = null, $primkey = false, $key = '')
    {
        if (! empty($key)   && ! empty($this->cache)) {
            $memflag = $this->cache->get($key);
            if ($memflag !== false) {
                return $memflag;
            }
        }

        $rows = parent::getRows( $table, $fields, $where, $append, $sort, $limit, $primkey);
        if ($rows && ! empty($key)   && ! empty($this->cache)) {
            $this->cache->set($key, $rows, $this->expire );
        }
        return $rows;
    }

    /**
     * 插入一行数据
     *
     * @param string $table
     *            数据表名
     * @param array $row
     *            插入数据的键值对数组
     * @return int 影响的行数。如果没有受影响的行，则返回 0。失败返回false
     */
    public function insertByKey( $table, $row, $key='')
    {
        $rowsAffected = parent::insert( $table,  $row );
        if (! empty($key) && $rowsAffected   && ! empty($this->cache)) {
            $this->cache->delete($key);
        }
        return $rowsAffected;
    }

    /**
     * 插入一行数据（重复则忽略）
     *
     * @param string $table
     *            数据表名
     * @param array $row
     *            插入数据的键值对数组
     * @return int 影响的行数。如果没有受影响的行，则返回 0。失败返回false
     */
    public function insertIgnoreByKey($table, $row , $key='')
    {
        $rowsAffected = parent::insertIgnore($table, $row);
        if (! empty($key) && $rowsAffected   && ! empty($this->cache)) {
            $this->cache->delete($key);
        }
        return $rowsAffected;
    }

    /**
     * 插入多行数据
     *
     * @param string $table
     *            数据表名
     * @param array $rows
     *            插入数据的二维键值对数组
     * @return int 影响的行数。如果没有受影响的行，则返回 0。失败返回false
     */
    public function insertRowsByKey($table, $rows , $key='')
    {
        // 执行SQL语句，返回影响行数，如果有错误，则会被捕获并跳转到出错页面

        $rowsAffected = parent::insertRows($table, $rows);
        if (! empty($key) && $rowsAffected   && ! empty($this->cache)) {
            $this->cache->delete($key);
        }
        return $rowsAffected;
    }

    /**
     * 更新一条记录的信息，能够同步缓存的数据，适用于缓存的Key中为1维数组的情况
     *
     * @param string $table 表名
     *
     * @param string $where 更新条件
     *
     * @param array $row 更新内容
     *
     * @param string $key 缓存键名
     *
     * @return int 影响的行数。如果没有受影响的行，则返回 0。失败返回false
     */
    public function updateByKey($table, $where, $row, $key = '')
    {

        list( $ret , $affected_rows ) = parent::update($table, $row, $where);
        if (! empty($key) && $ret   && ! empty($this->cache)) {
            $cacheFlag = $this->cache->get($key);
            if ($cacheFlag) {
                $this->cache->delete($key);
            }
        }
        return [$ret , $affected_rows ];
    }

    /**
     * 替换数据，并同步缓存
     * @param string $table
     * @param string $where
     * @param [] $row
     * @param string $key
     */
    public function replaceByKey( $table, $row, $key = '' )
    {
        $rowsAffected = parent::replace( $table, $row );
        if (! empty($key) && $rowsAffected &&   ! empty($this->cache)) {
            $cacheFlag = $this->cache->get($key);
            if (  $cacheFlag!==false) {
                $this->cache->delete($key);
            }
        }
        return $rowsAffected;
    }


    /**
     * 删除数据
     *
     * @param string $table
     *            表名
     * @param string $where
     *            查询条件
     * @param $key string
     *            缓存键名
     * @return int 影响的行数。如果没有受影响的行，则返回 0。失败返回false
     */
    public function deleteByKey( $table, $where, $key = '' )
    {
        $rowsAffected = parent::delete($table, $where);
        if (! empty($key) && $rowsAffected   && ! empty($this->cache)) {
            $this->cache->delete($key);
        }
        return $rowsAffected;
    }




    /**
     * 字段值自增并更新缓存
     *
     * @param string $field
     *            要自增的字段名
     * @param integer $id
     *            要自增的行id
     * @param string $key
     *            缓存键名
     * @return boolean
     */
    public function incByKey( $field, $id, $primkey = 'id',  $inc_value = 1 ,$key )
    {
        $inc_value = intval( $inc_value );
        $ret = parent::inc( $field, $id ,$primkey , $inc_value );
        if ($key && $ret && is_object($this->cache)) {
            $this->cache->inc($key , $inc_value);
        }
        return $ret;
    }

    /**
     * 字段值自减并更新缓存
     *
     * @param string $field
     *            要自减的字段名
     * @param integer $id
     *            要自减的行id
     * @param string $key
     *            缓存键名
     * @return boolean
     */
    public function decByKey ($field, $id, $primkey = 'id', $dec_value = 1 ,$key = '' )
    {
        $dec_value = intval( $dec_value );
        $ret = parent::dec( $field, $id ,$primkey ,$dec_value );
        if ($key && $ret && is_object($this->cache)) {
            $this->cache->dec( $key ,$dec_value );
        }
        return $ret;
    }


}