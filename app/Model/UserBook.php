<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/28
 * Time: 16:22
 */

namespace App\Model;


class UserBook extends BaseModel
{
    //如不指定则自动类名作为数据库表名
    protected $table = 'user_book';
}