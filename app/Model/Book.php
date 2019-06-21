<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/13
 * Time: 11:19
 */

namespace App\Model;


class Book extends BaseModel
{
    protected $table = 'books';
    protected $pk = 'uuid';
    protected $autoPk = false;

    protected function genPk()
    {
       return floor(mt_rand(10000000,99999999));
    }

}