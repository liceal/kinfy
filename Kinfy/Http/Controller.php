<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/11
 * Time: 10:04
 */

namespace Kinfy\Http;


class Controller
{
    public static $conf = [
        'delimiter' => '@',
        'namespace' => '\\App\\Controller\\',
        'global_prefix' => 'before',
        'global_suffix' => 'after',

        'prefix' => 'before',
        'suffix' => 'after',

        'band' => [
            //'login'
        ]
    ];

    public static function execMethod($obj, $method, $params=[])
    {
        if (method_exists($obj, $method)) {
            $obj->{$method}(...$params);
        }
    }

    public static function run($callback, $params)
    {

        if (is_callable($callback)) {
            call_user_func($callback, ...$params);
        } else {
            list($class, $method) = explode(self::$conf['delimiter'], $callback);
            $class = self::$conf['namespace'] . $class;
            $obj = new $class();

            //如果是禁止执行的方法，则不执行
            if (in_array($method, self::$conf['band'])) {
                die("{$method}方法被禁用");
            } else {
                //先执行全局前置方法
                if (isset(self::$conf['global_prefix'])) {
                    self::execMethod($obj, self::$conf['global_prefix']);
                }

                //执行前置
                if (isset(self::$conf['prefix'])) {
                    self::execMethod($obj, self::$conf['prefix'] . $method, $params);
                }

                $obj->{$method}(...$params);

                //执行后置
                if (isset(self::$conf['suffix'])) {
                    self::execMethod($obj, self::$conf['suffix'] . $method, $params);
                }

                //最后执行全局后置方法
                if (isset(self::$conf['global_suffix'])) {
                    self::execMethod($obj, self::$conf['global_suffix']);
                }


            }


        }


    }


}