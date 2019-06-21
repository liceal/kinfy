<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/26
 * Time: 14:59
 */

namespace Kinfy\Http;

use Kinfy\Config\Config;

class Router
{


    public static $delimiter = '@';
    public static $namespace = '';
    public static $url_pre = [];

    //存放路由过滤的中间件
    public static $middlewares = [];

    //存放当前注册的所有的路由规则
    public static $routes = [];
    //存放当前注册的所有正则表达式路由规则
    public static $re_routes = [];
    //存放正则参数全局默认规则
    public static $default_rule = [];

    //路由匹配中的回调方法，默认为空
    public static $onMatch = null;
    //当路由未匹配的时候执行的回调函数，默认为空
    public static $onMissMatch = null;


    public static function rule($param_name, $pattern)
    {
        self::$default_rule[$param_name] = $pattern;
    }

    /**
     *
     * @param $pre //参数
     * @param $callback //回调方法
     */
    public static function group($pre, $callback)
    {
        $p = ''; // 所有前缀
        $m = ''; // 所有中间键
        if (is_array($pre)) {
            //路由前缀
            if (isset($pre['prefix']) && $pre['prefix']) {
                $p = $pre['prefix'];
            }

            //路由中间键
            if (isset($pre['middleware']) && $pre['middleware']) {
                $m = $pre['middleware'];
            }
        } else {
            //如果不是一个数组，则默认里面的字符串是用于 路由前缀
            $p = $pre;
        }

        $p && array_push(self::$url_pre, $p);
        $m && array_push(self::$middlewares, $m);

        if (is_callable($callback)) {
            // call_user_func — 把第一个参数作为回调函数调用
            call_user_func($callback);
        }

        $p && array_pop(self::$url_pre);
        $m && array_pop(self::$middlewares);
    }

    //添加一条路由
    private static function addRoute($reqtype, $pattern, $callback, $re_rule = null)
    {
        //var_dump($re_rule);

        $reqtype = strtoupper($reqtype);
        $pattern = self::path(implode('/', self::$url_pre) . self::path($pattern));

        $route = [
            'callback' => $callback,
            'middlewares' => self::$middlewares
        ];

        //判断一下是否是正则路由
        $is_regx = strpos($pattern, '{') !== false;

        if (!$is_regx) {
            self::$routes[$reqtype][$pattern] = $route;
        } else {

            $pattern_raw = $pattern;
            //先找出占位符的名称
            $is_matched = preg_match_all('#{(.*?)}#', $pattern, $pnames);
            if ($is_matched) {
                //占位符默认替换的规则为全部
                foreach ($pnames[1] as $p) {
                    $pname = str_replace('?', '', $p);

                    $rule = '.+';

                    if (is_array($re_rule) && isset($re_rule[$pname])) {

                        $rule = $re_rule[$pname];

                    } else if (isset(self::$default_rule[$pname])) {

                        $rule = self::$default_rule[$pname];

                    } else if (strpos($p, '?') !== false) {
                        $rule = '.*';
                    }

                    $pattern = str_replace(
                        '{' . $p . '}',
                        '(' . $rule . ')',
                        $pattern
                    );
                }
            }

            $route = [
                'pattern_raw' => $pattern_raw,
                'pattern_re' => '#^' . $pattern . '$#',
                'callback' => $callback,
                'middlewares' => self::$middlewares
            ];
            self::$re_routes[$reqtype][$pattern_raw] = $route;
        }


    }

    public static function __callStatic($name, $args)
    {
        if (count($args) >= 2) {
            self::addRoute($name, ...$args);
        }
    }

    public static function match($reqtype_arr, $pattern, $callback)
    {
        foreach ($reqtype_arr as $reqtype) {
            self::addRoute($reqtype, $pattern, $callback);
        }

    }

    private static function path($path)
    {
        return '/' . trim($path, '/');
    }

    //执行$routes数组里的转发规则
    public static function dispatch()
    {
        //非正则路由集合
        $routes = self::$routes;
        //正则路由集合
        $re_routes = self::$re_routes;
//        print_r($routes);
//        print_r($re_routes);
//        die;


        //获取请求方法，GET，POST，PUT
        $reqtype = strtoupper($_SERVER['REQUEST_METHOD']);

        //获取请求地址，默认为/
        $url = $_SERVER['REQUEST_URI'] ? rtrim($_SERVER['REQUEST_URI'],'/') : '/';

        $args_pos = strpos($url, '?');

        if ($args_pos !== false) {
            $url = substr($url, 0, $args_pos);
        }

        //如果请求的存在[GET][/abc]
        $is_matched = false;
        //当前访问url路由所用到的方法
        $callback = null;
        $params = [];
        //存放当前url路由所用到的中间键
        $middlewares = [];


        if (isset($routes['ANY'][$url])) {
            $callback = $routes['ANY'][$url]['callback'];
            $middlewares = $routes['ANY'][$url]['middlewares'];
            $is_matched = true;
        } else if (isset($routes[$reqtype][$url])) {
            $callback = $routes[$reqtype][$url]['callback'];
            $middlewares = $routes[$reqtype][$url]['middlewares'];
            $is_matched = true;
        } else {
            if (isset($re_routes['ANY'])) {
                foreach ($re_routes['ANY'] as $route) {
                    $is_matched = preg_match($route['pattern_re'], $url, $params);
                    if ($is_matched) {
                        $callback = $route['callback'];
                        $middlewares = $route['middlewares'];
                        array_shift($params);
                        break;
                    }
                }
            }
            if (!$is_matched && isset($re_routes[$reqtype])) {
                foreach ($re_routes[$reqtype] as $pattern => $route) {
                    $is_matched = preg_match($route['pattern_re'], $url, $params);
                    if ($is_matched) {
                        $callback = $route['callback'];
                        $middlewares = $route['middlewares'];
                        array_shift($params);
                        break;
                    }
                }
            }
        }

        if ($is_matched) {
            //先做中间件数组扁平化
            //先做中间件
            //这里将执行中间键指向的类 然后执行方法
            foreach ($middlewares as $ms) {
                // 这里第一次循环 执行这些类的 handle方法
                foreach ($ms as $m) {
                    if (is_callable($m)) {
                        call_user_func($m);
                    } else {
                        // 到middleware配置文件里 获取$m 的实例
                        // 判断这个如果是数组 则遍历里面的类名 实例化 执行handle()方法
                        $mclass = Config::get('middleware.' . $m);
                        if (is_array($mclass)) {
                            foreach ($mclass as $mc) {
                                $mobj = new $mc;
                                $mobj->handle();
                            }
                        } else {
                            $mobj = new $mclass;
                            $mobj->handle();
                        }
                    }
                }
                //遍历第二次 执行handle2方法
                foreach ($ms as $m) {
                    if (!is_callable($m)) {
                        // 到middleware配置文件里 获取$m 的实例
                        // 判断这个如果是数组 则遍历里面的类名 实例化 执行handle2()方法
                        $mclass = Config::get('middleware.' . $m);
                        if (is_array($mclass)) {
                            foreach ($mclass as $mc) {
                                $mobj = new $mc;
                                if (is_callable($mobj->handle2())){
                                    $mobj->handle2();
                                }
                            }
                        } else {
                            $mobj = new $mclass;
                            if (is_callable($mobj->handle2())){
                                $mobj->handle2();
                            }
                        }
                    }
                }

            }


            if (is_callable(self::$onMatch)) {
                call_user_func(self::$onMatch, $callback, $params);
            } else {

                if (is_callable($callback)) {
                    call_user_func($callback, ...$params);
                } else {
                    //echo $callback;
                    list($class, $method) = explode(self::$delimiter, $callback);
                    $class = self::$namespace . $class;
                    $obj = new $class();
                    $obj->{$method}(...$params);
                }

            }


        } else {
            if (is_callable(self::$onMissMatch)) {
                call_user_func(self::$onMissMatch);
            } else {
                header("HTTP/1.1 404 Not Found");
                exit;
            }
        }
    }

}