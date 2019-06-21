<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/26
 * Time: 14:23
 */

use Kinfy\Http\Router;
use Kinfy\Http\Controller;
use Kinfy\Config\Config;


require_once __DIR__ . './../vendor/autoload.php';

//优先设置配置文件的根目录
Config::setBaseDir(__DIR__ . './../app/config/');


/**
 * 动态创建不存在的类Facade
 * 注册一个自动加载函数
 * 写了这个方法后  之后的use或者View 不存在的类直接创建
 * $interfaces 得到指定类 的 所有接口
 * $provider_interface 判断的接口
 *  = app.php['provider_interface'][$name]
 */
spl_autoload_register(function ($name) {
    $provider = Config::get('app.providers.' . $name);
    if (
        strpos($name, '\\') === false
        &&
        $provider
    ) {
        //如果定义了借口，则做借口判断
        $provider_interface = Config::get('app.provider_interface.' . $name);
        //约定接口 不为空
        if (!$provider_interface) {
            $interfaces = class_implements($provider);
            //如果这个类里面 没有继承 这个接口 则抛出错误
            if ($provider_interface && !isset($interfaces[$provider_interface])) {
                die("{$provider} 必须实现 {$provider_interface} 接口");
            }
        }
        //动态创建类
        eval("
        class {$name} extends \Kinfy\Facade\Facade{
            protected static \$provider = '{$provider}';
        }
        ");
    }
});


//View::set('title','123123');

//View::show('index');

//echo Config::get('app.providers.View');

if (Config::get('app.status') == 'SHUTDOWN') {
    //View::show('shutdown');
    die('网站维护中！');
}

//优先加载用户自定义的函数
//file_exists — 检查文件或目录是否存在
if (file_exists(__DIR__ . './../app/common/common_functions.php')) {
    require_once __DIR__ . './../app/common/common_functions.php';
}

//加载系统内置的函数
require_once __DIR__ . './../Kinfy/common/common_functions.php';

//加载路由文件
require_once __DIR__ . './../app/router/web.php';

//print_r(Router::$routes);

//路由未匹配中执行的回调函数
Router::$onMissMatch = function () {
    die('404');
};

//路由匹配中执行的回调函数
Router::$onMatch = function ($callback, $params) {
    Controller::run($callback, $params);
};

Router::dispatch();


