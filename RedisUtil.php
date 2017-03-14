<?php

define("__REDIS_HOST__", '127.0.0.1');
define("__REDIS_AUTH__", '123456');
define("__REDIS_PORT__", '6379');

/**
 * 获取RedisUtil单例
 * $redis = \RedisUtil::getInstance();
 */
class RedisUtil
{
    public static $_instance;
    private $redisInstance;

    private function __construct()
    {
        $this->redisInstance = new redis();
        if(false == $this->redisInstance->connect(__REDIS_HOST__, __REDIS_PORT__)) {
            return false;
        }

        if(false == $this->redisInstance->auth(__REDIS_AUTH__)) {
            return false;
        }

        //选择db1
        //$this->redisInstance->select(1);
        return true;
    }

    private function __clone()
    {
        return false;
    }

    public function __destruct()
    {
        $this->close();
    }

    //单例
    public static function getInstance()
    {
        if(!isset(self::$_instance)) {
            self::$_instance = new RedisUtil();
        }
        return self::$_instance;
    }

    public function close()
    {
        $this->redisInstance->close();
    }

    public function getRedisInstance()
    {
        return $this->redisInstance;
    }

    //******************************** String *****************************************
    public function getValue($key, $isJson = false)
    {
        $value = $this->redisInstance->get($key);
        if (!empty($value) && $isJson) {
            $value = json_decode($value, true);
        }

        return $value;
    }

    public function setValue($key, $value, $time = 0, $isJson = false)
    {
        if ($isJson) {
            $value = json_encode($value);
        }

        if (false == $this->redisInstance->set($key, $value)) {
            return false;
        }
        if (!empty($time)) {
            $this->setExpire($key, $time);
        }
        return true;
    }

    public function getJsonValue($key)
    {
        return $this->getValue($key, true);
    }

    public function setJsonValue($key, $value, $time = 0)
    {
        return $this->setValue($key, $value, $time, true);
    }

    public function incr($key, $time = 0)
    {
        if (false == $this->redisInstance->incr($key)) {
            return false;
        }
        if (!empty($time)) {
            $this->setExpire($key, $time);
        }
        return true;
    }

    public function incrBy($key, $count, $time = 0)
    {
        if (false == $this->redisInstance->incrby($key, $count)) {
            return false;
        }
        if (!empty($time)) {
            $this->setExpire($key, $time);
        }
        return true;
    }

    public function decr($key, $time = 0)
    {
        if (false == $this->redisInstance->decr($key)) {
            return false;
        }
        if (!empty($time)) {
            $this->setExpire($key, $time);
        }
        return true;
    }

    public function decrBy($key, $count, $time = 0)
    {
        if (false == $this->redisInstance->decrBy($key, $count)) {
            return false;
        }
        if (!empty($time)) {
            $this->setExpire($key, $time);
        }
        return true;
    }

    //******************************** List *****************************************
    public function leftPop($key)
    {
        return $this->redisInstance->lPop($key);
    }

    public function rightPush($key, $value, $time = 0)
    {
        $this->redisInstance->rPush($key, $value);
        if (!empty($time)) {
            $this->setExpire($key, $time);
        }
    }

    /*
    * @range
    * 返回名称为key的list的所有元素
    */
    public function range($key, $start = 0, $stop = -1)
    {
        return $this->redisInstance->lRange($key, $start, $stop);
    }

    /**
     * 返回的列表的大小。如果列表不存在或为空，该命令返回0。如果该键不是列表，该命令返回FALSE
     * @param $key
     * @return mixed
     */
    public function size($key)
    {
        return $this->redisInstance->lSize($key);
    }

    //******************************** Set *****************************************
    /**
     * 判断成员元素是否是集合的成员
     * @param $key
     * @param $value
     * @return mixed
     */
    public function isMember($key, $value)
    {
        return $this->redisInstance->sIsMember($key, $value);
    }

    /**
     * 向集合添加一个成员
     * @param $key
     * @param $value
     * @return mixed
     */
    public function add($key, $value)
    {
        return $this->redisInstance->sAdd($key, $value);
    }
    /*
     * @smember
     * 返回名称为key的set的所有元素
     */
    public function sMember($key)
    {
        return $this->redisInstance->smembers($key);
    }

    /**
     * 移除并返回集合中的一个随机元素
     * @param $key
     * @return mixed
     */
    public function sPop($key)
    {
        return $this->redisInstance->sPop($key);
    }

    /**
     * 返回集合中元素的数量
     * @param $key
     * @return mixed
     */
    public function sCard($key)
    {
        return $this->redisInstance->sCard($key);
    }

    //******************************** key *****************************************
    public function existsKey($key)
    {
        return $this->redisInstance->exists($key);
    }

    public function delete($key)
    {
        return $this->redisInstance->del($key);
    }

    public function setExpire($key, $time)
    {
        return $this->redisInstance->expire($key, $time);
    }

    public function getTtl($key)
    {
        return $this->redisInstance->ttl($key);
    }


    /*返回当天剩下的秒数*/
    protected static function _distanceNextDay()
    {
        $now = time();
        $day = strtotime(date('Y-m-d'));
        $past = $now - $day;
        //一天总秒数减去当天已过的秒数
        return 86400 - $past;
    }

    /*通用数据缓存时间*/
    protected static function dataCacheExpire()
    {
        return 7200 + self::getRandomTime();
    }

    /**
     * 获取随机时间(6分钟之内)
     * 高并发情况下，redis有效时间加上随机数，可以有效防止短时间内所有key失效造成雪崩
     */
    protected static function getRandomTime()
    {
        return mt_rand(1,360);
    }
}
