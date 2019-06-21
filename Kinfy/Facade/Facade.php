<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/31
 * Time: 11:45
 */

namespace Kinfy\Facade;
class Facade
{
    protected static $provider = '';
    protected static $instance = [];
    /**
     * 静态转动态
     * 因为当执行静态代码时，自身有一个实例static::$instance
     * 返回静态的共用的实例
     * @return mixed //实例
     */
    public static function getInstance()
    {
        $instance = static::$provider;
        if (!isset(static::$instance[$instance])) {
            static::$instance[$instance] = new $instance;
        }
        return static::$instance[$instance];
    }
    //当调用不存在的静态方法的时候，自动静态转动态
    public static function __callStatic($name, $arguments)
    {
        return static::getInstance()->{$name}(...$arguments);
    }

}