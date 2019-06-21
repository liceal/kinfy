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

        View::setTheme('school');
        View::setSuffix('.html');
        View::set('title', 'liceal');
        View::set('subtitle', '首页');
        View::set('header_icon', [
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