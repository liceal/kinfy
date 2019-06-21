<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/8
 * Time: 11:02
 */

namespace App\Model;

class Article extends BaseModel
{

    public static function getHot($num = 10)
    {
        return self::where('is_hot', 1)
            ->take($num)
            ->orderBy('id', 'desc')
            ->get();
    }


}