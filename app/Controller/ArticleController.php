<?php
/**
 * use View 或者 aaa 都会自动去app/config/app.php 配置文件下面  找到已经注册的 aaa这个东西
 * 然后这里的aaa 就是 Kinfy/View/aa.php 的实例
 * test()就是aaa里面的一个方法！！！
 */

namespace App\Controller;

use Kinfy\DB\DB;
use View;

class ArticleController extends BaseController
{
    public function index()
    {
        View::show('index');
    }

    public function news()
    {
        copyright();
        View::show('news');
    }

    public function news2()
    {
        View::show('news2');
    }

    public function news3()
    {
        View::show('news3');
    }


}