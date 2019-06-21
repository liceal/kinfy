<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/28
 * Time: 16:15
 */

namespace App\Model;


class Test
{
    public static $obj = null;
    public static function __callStatic($name, $arguments)
    {
        if(!static::$obj){
            static::$obj = new static();
        }
        self::$obj->{$name}();
    }


    public  function __call($name, $arguments)
    {
        echo '调用实例方法:'.$name.'<br>';
    }


}