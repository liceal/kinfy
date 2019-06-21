<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/8
 * Time: 11:07
 */

namespace Kinfy\Model;

use Kinfy\DB\DB;

class Model
{
    //存放当前实例
    protected static $instance = null;
    //当前实例对应的数据表
    protected $table = '';
    //当前实例的数据库对象
    protected $DB = null;
    //实例属性
    public $properties = [];
    //数据库列名
    protected $fields = [];
    //数据库列名别名，键：数据库列名，值：对象属性名
    protected $field2property = [

    ];
    //实例属性名对应的数据库列名
    private $property2field = [

    ];
    //是否自动大小写和下划线之间进行转换
    protected $autoCamelCase = true;

    //模型对应数据库的主键
    protected $pk = 'id';

    //主键是否是由数据库自动生成（比如，自增主键）
    protected $autoPk = true;

    //不允许批量添加的数据库字段
    protected $guarded = [];

    //允许批量添加的数据库字段
    protected $fillable = [];

    //是否可以查看指定的数据库字段
    public $fieldView = [
        'password' => '***',
    ];


    //构造函数，初始化当前实例对应的数据表，初始化当前实例对应的数据库对象
    //初始化的时候，自动读取一条数据
    public function __construct($id = null)
    {

        if (!$this->table) {
            $class = get_class($this);
            $this->table = substr($class, strrpos($class, '\\') + 1);
        }

        if (!$this->DB) {
            $this->DB = new DB();
        }

        $this->DB->table($this->table);
        if ($id) {
            $this->DB->where($this->pk, $id);
            //不能调用DB的first,因为自己的first有做字段过滤
            $this->first();
        }
    }

    //如果主键不是数据库自动生成，则应当重写主键生成方法
    //前缀目的32长度，加上uniqid自身的13长度，共45
    protected function genPk()
    {
        return uniqid(md5(microtime(true)));
    }


    //判断给定的字符串是否是大写字母
    private function isUpper($str)
    {
        return ord($str) > 64 && ord($str) < 91;
    }

    /**
     * camelSnake 转换成  camel_snakecamelSNAKE
     * camel_snakecamel_SNAKE
     * camel_snakecamel_SNAKeName
     * camel_snake_name
     * @param $str
     * @return string
     */
    protected function camel2snake($str)
    {
        $s = '';
        for ($i = 0; $i < strlen($str); $i++) {
            //从第二个开始判断，如果是大写字母，且该字母前一个不是大写或者不是下划线
            if (
                $i > 0 &&
                $this->isUpper($str[$i]) &&
                !$this->isUpper($str[$i - 1]) &&
                $str[$i - 1] != '_'
            ) {
                $s .= '_';
            }
            $s .= $str[$i];
        }
        return strtolower($s);
    }

    //camel_snake 转换成 camelSnake
    protected function snake2camel($str)
    {
        $c = '';
        $str_arr = explode('_', $str);

        foreach ($str_arr as $k => $s) {
            //除第一个单词外，首字母大写
            if ($k > 0) {
                $s = ucfirst($s);
            }
            $c .= $s;
        }
        return $c;
    }

    public function property2field($name)
    {
        if (empty($this->property2field)) {
            $this->property2field = array_flip($this->field2property);
        }

        if (isset($this->property2field[$name])) {
            return $this->property2field[$name];
        }

        if ($this->autoCamelCase) {
            return $this->camel2snake($name);
        }

        return $name;
    }

    //数据库列名转属性名
    public function field2property($name)
    {
        if (isset($this->field2property[$name])) {
            return $this->field2property[$name];
        }

        if ($this->autoCamelCase) {
            return $this->snake2camel($name);
        }

        return $name;
    }

    //往数据库添加的时候，属性名为fieldName要转换成field_name
    //同时要删除禁止批量赋值的列
    protected function filterFields($data = [])
    {
        //批量添加的数据，经过黑白名单过滤
        if ($data) {
            foreach ($data as $k => $v) {
                $k = $this->property2field($k);
                //白名单优先
                //如果设置了白名单，以白名单为准，否则以黑名单为准
                if ($this->fillable) {
                    if (!in_array($k, $this->fillable))
                        continue;
                }

                if ($this->guarded) {
                    if (in_array($k, $this->guarded))
                        continue;
                }

                $this->fields[$k] = $v;
            }
        }
        //手工添加的数据不过滤
        foreach ($this->properties as $k2 => $v2) {
            $k2 = $this->property2field($k2);
            $this->fields[$k2] = $v2;
        }

    }

    //数据库读取出来的数据，列名为field_name要转换成fieldName
    protected function filterProperties($data)
    {
        //没有自定义列名转换规则，同时也关闭了自动转换规则，且没有隐藏列，则不循环直接返回
        if (empty($this->field2property) && !$this->autoCamelCase && empty($this->fieldView)) {
            return $data;
        }

        $new_data = [];
        foreach ($data as $k => $v) {
            $k2 = $this->field2property($k);

            //过滤隐藏字段
            if (isset($this->fieldView[$k])) {
                $new_data[$k2] = $this->fieldView[$k];
            } else {
                $new_data[$k2] = $v;
            }
        }
        return $new_data;
    }

    //当读取一个不存在的属性名的时候，自动到当前实例的properties属性数组里去获取
    public function __get($name)
    {
        return $this->properties[$name];
    }

    //当设置一个不存在的属性名的时候，自动将值存取到当前实例的properties属性
    public function __set($name, $value)
    {
        $this->properties[$name] = $value;
    }

    //是否是DB的结束操作
    private function isTerminalMethod($name)
    {
        $name = strtolower($name);
        $m = [
            'get',
            'first',
        ];
        return in_array($name, $m);
    }

    //在该数组里的方法，自动会调用自身带下划线对应的方法
    private function isSelfMethod($name)
    {
        $name = strtolower($name);
        $m = [
            'add',
            'update',
            'save',
            'del'
        ];
        return in_array($name, $m);
    }

    //当调用一个不存在的实例方法时则自动调用该魔术方法
    public function __call($name, $arguments)
    {

        $name = strtolower($name);
        //如果是调用自身的，则不调用DB
        if ($this->isSelfMethod($name)) {
            $sname = '_' . $name;
            return $this->{$sname}(...$arguments);
        }

        $r = $this->DB->{$name}(...$arguments);
        //如果不是结束节点（获取数据）
        if (!$this->isTerminalMethod($name)) {
            return $this;
        }


        if (empty($this->field2property) && !$this->autoCamelCase) {
            return $r;
        }

        if (is_array($r)) {
            if ($name == 'get') {
                foreach ($r as &$data) {
                    $data = $this->filterProperties($data);
                }
            } else if ($name == 'first') {
                $r = $this->filterProperties($r);
                //如果是读取一条，则自动设置对象的属性
                foreach ($r as $k => $v) {
                    $this->properties[$k] = $v;
                }
            }

        }

        return $r;

    }

    //因为当执行静态代码时，自身有一个实例static::$instance
    //返回静态的共用的实例
    public static function getInstance()
    {
        $sub_class = get_called_class();

        if (!isset(static::$instance[$sub_class])) {
            static::$instance[$sub_class] = new static();
        }
        return static::$instance[$sub_class];
    }

    //当调用不存在的静态方法的时候，自动静态转动态
    public static function __callStatic($name, $arguments)
    {
        return static::getInstance()->{$name}(...$arguments);
    }

    //往数据库添加的时候，是否有主键数据
    public function havePk()
    {
        return isset($this->fields[$this->pk]) && $this->fields[$this->pk] !== null;
    }

    //新增一条记录
    public function _add($data = [])
    {
        //预先处理写进去的数据库字段，把属性名转换成数据库字段名
        $this->filterFields($data);

        if ($this->autoPk) {
            unset($this->fields[$this->pk]);
        } else if (!$this->havePk()) {
            $this->fields[$this->pk] = $this->genPk();

        }

        return $this->DB->insert($this->fields);

    }

    public function _update($data = [])
    {
        $this->filterFields($data);

        $k = $this->pk;
        $v = $this->fields[$this->pk];

        unset($this->fields[$this->pk]);
        return $this->DB
            ->where($k, '=', $v)
            ->update($this->fields);
    }

    //新增或者更新方法，取决于主键是否有值
    public function _save($data = [])
    {
        $this->filterFields($data);
        //如果没有主键，则添加，否则更新
        if (!$this->havePk()) {

            if ($this->autoPk) {
                unset($this->fields[$this->pk]);
            }
            $this->fields[$this->pk] = $this->genPk();
            return $this->DB->insert($this->fields);

        } else {

            $k = $this->pk;
            $v = $this->fields[$this->pk];

            unset($this->fields[$this->pk]);
            return $this->DB
                ->where($k, '=', $v)
                ->update($this->fields);

        }
    }

    public function _delete($id = null)
    {

        $k = $this->pk;
        $v = $this->fields[$this->pk] ?? null;
        if ($id) {
            $this->DB->where($k, '=', $id);
        } else if ($v) {
            $this->DB->where($k, '=', $v);
        } else if ($id === null){
            die("没有主键ID值");
        }

        return $this->DB->delete();

    }

}