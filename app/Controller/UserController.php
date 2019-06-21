<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/26
 * Time: 14:19
 */

namespace App\Controller;

use Kinfy\DB\DB;

use App\Model\Article;
use App\Model\User;

use App\Model\Test;
use App\Model\Book;

use Kinfy\View\View;


class UserController extends BaseController
{

    public function login()
    {

        $user = new User();
        $user->test();
        echo '登录页面!!!!';
    }

    public function beforeDel($id)
    {
        if ($id == 1) {
            echo '超级管理员，禁止删除!';
            die;
        }
    }


    public function del($id)
    {

        //echo '用户删除页面', $id;

//        $view = new View();
//        $view->show('user/del');

    }

    //查看用户的所有文章
    public function article($user_id)
    {
        $user = new User($user_id);
        $r = $user->getArticle();
        //print_r($r);

        View::set('article',$r);
        View::show('article/list');

    }

    public function copy($user_id)
    {
        $art = new User($user_id);
        $art->name .= '-副本';
        $art->add();
    }

    public function userList()
    {
        $user = new User();
        $isadmin = false;
        //根据权限动态判断制定字段是否有访问权限
        if (!$isadmin) {
            $user->fieldView['mobile'] = '***';
        }
        $r = $user->get();
        print_r($r);
    }

    public function add()
    {
        $user = new User();
        $user->isAdmin = 1;

        $user->save($_POST);

    }

    public function borrow($user_id,$book_id)
    {
        $user = new User($user_id);
        $book = new Book($book_id);
        $user->borrow($book);
    }


}