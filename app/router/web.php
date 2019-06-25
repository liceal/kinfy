<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/26
 * Time: 14:57
 */

use Kinfy\Http\Router;

Router::group(['middleware'=>['A','B'],'prefix'=>'user'],function (){
    Router::get('login','UserController@login');
});

Router::group('admin',function (){
    Router::get('login','UserController@login');
});

//Router::GET(
//    '/user/{uid}/article',
//    'UserController@article',
//    ['uid'=>'\d+']
//    );
//
//Router::POST(
//    '/user/{uid}/copy',
//    'UserController@copy',
//    ['uid'=>'\d+']
//);

Router::GET('/user', 'UserController@userList');

Router::GET('/test', 'ArticleController@echoAbc');

Router::POST('/user/add','UserController@add');

Router::POST('/user/{uid}/borrow/{bid}','UserController@borrow');

Router::GET('/','ArticleController@index');

Router::GET('/news','ArticleController@news');
Router::GET('/news2','ArticleController@news2');
Router::GET('/news3','ArticleController@news3');





