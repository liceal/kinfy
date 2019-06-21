<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/28
 * Time: 16:22
 */

namespace App\Model;


use Kinfy\Config\Config;

class User extends BaseModel
{
    //该模型绑定的数据表
    //如不指定则自动类名作为数据库表名
    protected $table = 'users';

    //protected $autoCamelCase = false;

    //数据库列名转对象属性
    //key:数据库列名
    //value:模型属性名
    protected $field2property = [
        'user_name' => 'name',
    ];


    //数据库列在查询后的显示字符
    //一般情况下配合权限过滤
    public $fieldView = [
        'password' => '***',
    ];

    protected $guarded = [
        'is_admin'
    ];

    public function borrow($book)
    {

        UserBook::add([
            'user_id' => $this->id,
            'book_id' => $book->uuid
        ]);

    }

    public function test(){
//        $data = $this->DB->table('article')->where('cate_id','=','3')->OR()->L()->where('cate_id','=','3')->AND()->whereNotNull('cate_id')->R()->get();
        $article = $this->DB->table('article');
        $data = $article->like('title','文章')->get();

        echo '<br>';
        print_r($data);
    }


}