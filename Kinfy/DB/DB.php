<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/18
 * Time: 9:47
 */

namespace Kinfy\DB;

use Kinfy\Config\Config;
use PDO;

class DB
{

    public $pdo = null;

    /**
     * 筛选列
     * @var array
     */
    public $fields = [];

    /**
     * 筛选条件
     * @var array
     */
    public $where = [];

    /**
     * 表名
     * @var string
     */
    public $table = '';

    /**
     * 范围截取
     * @var array
     */
    public $limit = [];

    /**
     * 生成的sql语句
     * @var array
     */
    public $sql = [];

    /**
     * 连接的表
     * @var string
     */
    public $join = '';

    /**
     * 排序规则
     * @var array
     */
    public $orderby = [];

    /**
     * 链接数据库
     * DB constructor.
     * @param null $conf
     */
    public function __construct($conf = null)
    {
        if (!$conf) {
//            $conf = [
//                'dbms' => 'mysql',     //数据库类型
//                'host' => '127.0.0.1', //数据库主机名
//                'name' => 'kinfy',      //使用的数据库
//                'user' => 'root',      //数据库连接用户名
//                'pass' => 'root',      //对应的密码
//            ];
            $conf = Config::get('db');
        }
        $dsn = "{$conf['dbms']}:host={$conf['host']};dbname={$conf['name']}";
        $this->pdo = new PDO($dsn, $conf['user'], $conf['pass']);
        //设置全局属性，默认读取的数据以关联数组的形式返回
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    }

    /**
     * 设置表名
     * @param $name
     * @return $this
     */
    public function table($name)
    {
        $this->table = $name;
        return $this;
    }

    /**
     * 选择列
     * 例子：'title','cate_id'
     * @param mixed ...$fields //列名，多个用数组
     * @return $this
     */
    public function select(...$fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * 范围选取
     * @param $offset //开始下标
     * @param $num //长度
     * @return $this
     */
    public function limit($offset, $num)
    {
        $this->limit = [$offset, $num];
        return $this;
    }

    /**
     * 长度查询，从开头选取指定长度数据
     * @param $num //长度
     * @return DB
     */
    public function take($num)
    {
        return $this->limit(0, $num);
    }

    /**
     * 选取第一条数据
     * 不用->get()返回，默认返回数据
     * @return bool
     */
    public function first()
    {
        $r = $this->take(1)->get();
        if (isset($r[0])) {
            return $r[0];
        } else {
            return false;
        }
    }

    /**
     * 筛选条件
     * @param $field //键
     * @param $op //关系符
     * @param null $val //对应值
     * @return DB
     */
    public function where($field, $op, $val = null)
    {
        $this->_where($field, $op, $val);
        return $this;
    }

    /**
     * 筛选空值
     * 筛选指定键，值为null的
     * @param $field //键
     * @return DB
     */
    public function whereNull($field)
    {
        return $this->_where($field, 'IS', 'NULL');
    }

    /**
     * 筛选不为空值，同whereNull
     * @param $field
     * @return DB
     */
    public function whereNotNull($field)
    {
        return $this->_where($field, 'IS NOT', 'NULL');
    }

    /**
     * 模糊查询
     * @param $field //查询列
     * @param $val //模糊查询条件
     * @return $this
     */
    public function like($field,$val){
        $this->_where($field,'like',"%{$val}%");
        return $this;
    }

    /**
     * 筛选方法
     * @param $field //键
     * @param $op // 关系符
     * @param $val //值
     * @param $type //类型 and or
     * @return $this //DB实例
     */
    protected function _where($field, $op, $val)
    {
        if ($val === null) {
            $val = $op;
            $op = '=';
        }
        $this->where[] = [
            'condition' => [$field, $op, $val],
        ];

        return $this;
    }

    /**
     *   (  左括号
     */
    public function L()
    {
        $this->where[] = '(';
        return $this;
    }

    /**
     *   )  右括号
     */
    public function R()
    {
        $this->where[] = ')';
        return $this;
    }

    /**
     * AND 并且
     * @return $this
     */
    public function AND(){
        $this->where[] = 'AND';
        return $this;
    }

    /**
     * OR 或者
     * @return $this
     */
    public function OR(){
        $this->where[] = 'OR';
        return $this;
    }

    /**
     * 生成where语句
     * 里面包含了所有where筛选条件
     * @return array
     */
    private function genWhere()
    {
        //1.生成带?的条件语句
        $where = '';
        //2.生成?对应的值的数组
        $values = [];
        //$this->where[ ['cate_id', '=', 10], ['user_id','=',5] ]
        foreach ($this->where as $c) {
            if ($c == '(' || $c == ')' || $c == 'AND' || $c == 'OR') {
                $where .= $c;
            } else if (isset($c['condition'])) {
                list($field, $op, $val) = $c['condition'];
                $field = strpos($field,'.') !== -1 ? $field : '`'.$field.'`';
                $where .= " {$field} {$op} ? ";
                $values[] = $val;
            }
        }
        if ($where) {
            $where = ' WHERE ' . $where;
        }

        return [$where, $values];
    }

    /**
     * 生成sql语句
     * @return array
     */
    public function genSql()
    {

        if ($this->sql) {
            return $this->sql;
        }

        //1.准备SQL
        list($WHERE, $VAL) = $this->genWhere();
        $LIMIT = '';
        if ($this->limit) {
            $LIMIT = " LIMIT {$this->limit[0]},{$this->limit[1]} ";
        }

        $FIELDS = '*';
        if (!empty($this->fields)) {
            $sep = '';
            $FIELDS = '';
            foreach ($this->fields as $f) {
                $is_identifier = strpos($f, '.') !== false;
                if (!$is_identifier) {
                    $f = "`{$f}`";
                }
                $FIELDS .= "{$sep}{$f}";
                $sep = ',';
            }
        }

        $JOIN = $this->join;

        $ORDERBY = '';
        foreach ($this->orderby as $ob) {
            $is_identifier = strpos($ob[0], '.') !== false;
            if (!$is_identifier) {
                $ob[0] = "`{$ob[0]}`";
            }
            $ORDERBY .= "{$ob[0]} {$ob[1]},";
        }

        if ($ORDERBY) {
            $ORDERBY = ' ORDER BY ' . rtrim($ORDERBY, ',');
        }


        $this->sql['sql'] = "SELECT {$FIELDS} FROM {$this->table} {$JOIN} {$WHERE} {$ORDERBY} {$LIMIT}";
        $this->sql['value'] = $VAL;

        print_r($this->sql);

        return $this->sql;
    }

    /**
     * 连接表
     * 例子：
     * table('users')->join('user_book','id=user_id')->get();
     * @param $table //要连接的表名
     * @param $condition //连表查询条件
     * @return $this
     */
    public function join($table, $condition)
    {
        $this->join = " JOIN {$table} ON {$condition}";
        return $this;
    }

    /**
     * 排序
     * 对指定字段进行升序(asc)或者降序(降序)
     * @param $field //键
     * @param string $rule
     * @return $this
     */
    public function orderBy($field, $rule = 'DESC')
    {
        $this->orderby[] = [$field, $rule];
        return $this;
    }

    /**
     * 获取查询结果
     * @return array
     */
    public function get()
    {
        $this->genSql();
        //2.准备prepare语句和参数对应的数组值
        $pdosmt = $this->pdo->prepare($this->sql['sql']);
        $r = $pdosmt->execute($this->sql['value']);
        if (!$r) {
            print_r($pdosmt->errorInfo());
        }
        $this->clear();
        return $pdosmt->fetchAll();
    }

    /**
     * 清除sql配置
     */
    public function clear()
    {
        $this->fields = [];
        $this->where = [];
        //$this->table = '';,表名不清除，让模型只需赋值一次
        $this->limit = [];
        $this->sql = [];
        $this->join = '';
        $this->orderby = [];
    }

    /**
     * 插入数据数组,
     * 例子：table('users')->insert(['user_name'=>'赵四1','password'=>'123456'])
     * @param array $values //插入数据
     * @param bool $force_align //是否固定列
     * @return bool
     */
    public function insert($values, $force_align = true)
    {
        //如果不是二维数组，则封装成二维数组
        if (!is_array(reset($values))) {
            $values = [$values];
        }

        return $this->batchInsert($values, $force_align);
    }

    /**
     * 批量添加
     * 例子：table('users')->batchInsert([['user_name'=>'王五'],['user_name'=>'王五1']])
     * @param $values //数组，二维数组
     * @param bool $force_align //是否固定列
     * @return bool
     */
    public function batchInsert($values, $force_align = true)
    {
        if ($force_align) {
            $all_vals = [];
            //提取所有的值
            foreach ($values as $val) {
                ksort($val);
                foreach ($val as $v) {
                    $all_vals[] = $v;
                }
            }

            //$val是最后一条数据，获取最后一条数据的所有键，并用，分割
            //content,title
            $field_keys_str = implode(',', array_keys($val));

            //构造占位符
            $ph = [];
            $ph = array_pad($ph, count($val), '?');
            //?,?
            $ph = implode(',', $ph);

            $all_placeholder = '';
            for ($i = 0; $i < count($values); $i++) {
                $all_placeholder .= "({$ph}),";
            }
            //(?,?),(?,?),(?,?),(?,?),(?,?)
            $all_placeholder = rtrim($all_placeholder, ',');

            $this->sql = [
                'sql' => "INSERT INTO `{$this->table}` ({$field_keys_str}) VALUES {$all_placeholder} ",
                'value' => $all_vals
            ];

        } else {
            $this->sql['sql'] = '';
            $this->sql['value'] = [];
            foreach ($values as $val) {
                //构造占位符
                $ph = [];
                $ph = array_pad($ph, count($val), '?');
                $ph = implode(',', $ph);
                $field_keys_str = implode(',', array_keys($val));
                $this->sql['sql'] .= "INSERT INTO `{$this->table}` ({$field_keys_str}) VALUES ({$ph});\n";
                foreach ($val as $v) {
                    $this->sql['value'][] = $v;
                }
            }

        }


        return $this->execute();
    }

    /**
     * 更新数据
     * table('users')->where('user_name','=','王五1')->update(['user_name'=>'王五2'])
     * @param $data //键值对，['user_name'=>'王五2']
     * @return bool
     */
    public function update($data)
    {
        if (empty($data)) {
            return true;
        }
        //update article set title=? ,content=? where id=99
        $SET = ' SET ';
        $values = [];
        foreach ($data as $k => $v) {
            $SET .= "`{$k}` = ?,";
            $values[] = $v;
        }
        $SET = rtrim($SET, ',');

        list($WHERE, $VAL) = $this->genWhere();

        $this->sql['sql'] = "UPDATE `{$this->table}` {$SET} {$WHERE}";

        $this->sql['value'] = array_merge($values, $VAL);

        return $this->execute();
    }

    /**
     * 脱裤
     * @return bool
     */
    public function delete()
    {
        list($WHERE, $VAL) = $this->genWhere();

        $this->sql['sql'] = "DELETE FROM `{$this->table}` {$WHERE}";
        $this->sql['value'] =  $VAL;

        return $this->execute();
    }

    /**
     * 执行PDO(执行sql语句)
     * @param null $sql //sql语句 里面有 ? 代表参数
     * @param null $value // 参数 会填充?
     * @return bool
     */
    public function execute($sql = null, $value = null)
    {
        if (!$sql) {
            $sql = $this->sql['sql'];
        }
        if (!$value) {
            $value = $this->sql['value'];
        }

        $pdosmt = $this->pdo->prepare($sql);
        $r = $pdosmt->execute($value);
        if (!$r) {
            print_r($pdosmt->errorInfo());
        }
        $this->clear();

        return $r;
    }


}

