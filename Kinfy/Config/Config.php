<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/31
 * Time: 10:28
 */

namespace Kinfy\Config;
class Config
{
    //存放整个网站的配置信息
    protected static $conf = [];
    //系统配置文件夹根目录
    protected static $base_dir = '';

    public static function setBaseDir($dir)
    {
        self::$base_dir = $dir;
    }

    public static function get($key)
    {
//        print_r($key);
        $k = explode('.', $key, 5);
        $f = $k[0];
        if (!isset(self::$conf[$f])) {
            self::$conf[$f] = include self::$base_dir . $f . '.php';
        }

        switch (count($k)) {
            case 1:
                return self::$conf[$k[0]] ?? null;
            case 2:
                return self::$conf[$k[0]][$k[1]] ?? null;
            case 3:
                return self::$conf[$k[0]][$k[1]][$k[2]] ?? null;
            case 4:
                return self::$conf[$k[0]][$k[1]][$k[2]][$k[3]] ?? null;
            case 5:
                return self::$conf[$k[0]][$k[1]][$k[2]][$k[3]][$k[4]] ?? null;
        }
        return null;
    }

    /**
     * set('a.b.c.d.e','hello')
     * 最长获取a.b.c.d.e 五个
     * @param $key
     * @param $value
     */
    public static function set($key, $value)
    {
        $k = explode('.', $key, 5);
        switch (count($k)) {
            case 1:
                self::$conf[$k[0]] = $value;
                break;
            case 2:
                self::$conf[$k[0]][$k[1]] = $value;
                break;
            case 3:
                self::$conf[$k[0]][$k[1]][$k[2]] = $value;
                break;
            case 4:
                self::$conf[$k[0]][$k[1]][$k[2]][$k[3]] = $value;
                break;
            case 5:
                self::$conf[$k[0]][$k[1]][$k[2]][$k[3]][$k[4]] = $value;
                break;
        }
    }

}