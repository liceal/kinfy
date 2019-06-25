<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/26
 * Time: 14:19
 */

namespace App\Controller;
use View;

class BaseController
{

    public function __construct()
    {

        View::setTheme('school'); // 设置使用模板文件夹
        View::setSuffix('.html'); // 设置模板后缀
        View::set('title', 'liceal'); // 设置变量title 值为liceal
        View::set('subtitle', '首页');// 设置变量subtitle 值为 首页
        View::set('header_icon', [ //设置变量header_icon 值为键值对...
            ['class' => 'icon-location2', 'text' => '浙江温州山沟沟'],
            ['class' => 'icon-phone2', 'text' => '0577-666666'],
            ['class' => 'icon-mail', 'text' => 'liceal@linxianao.com'],
        ]);

    }

    public function before()
    {

        //echo '全局执行拦截<br>';
    }


}