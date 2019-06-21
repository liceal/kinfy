<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/17
 * Time: 11:29
 */

namespace Kinfy\View\engine;

class Blade implements IEngine
{

    //模板文件根目录
    public $base_dir;
    //模板子模板数组
    protected $sub_tpls = [];
    //模板对应的父模板路径数组
    protected $tpl_parents = [];
    //模板定界符
    public $tag = ['{', '}'];
    //要处理的模板字符串
    public $template = '';
    //当前解析的模板名称
    public $tpl = '';
    //模板文件后缀
    public $suffix = '';
    //母版占位符对应的文字
    protected $tag_body = [];


    public function compiling()
    {
        //layout又叫master支持，母版
        $this->extendsExp();
//
//

        //执行调用
        $this->includeExp();
//
//        //执行判断语句
//        $this->ifExp();
//        $this->elseifExp();
//        $this->elseExp();
//        $this->endifExp();
//
//        //循环语句
        $this->loopExp();
        $this->endloopExp();
//
//        //变量
        $this->varExp();

        //$this->template = '编译开始:' . $this->template . ':编译结束';
    }

    //正则替换模板
    protected function _replace($find, $replace)
    {
        //\d+
        //$pattern = /{\s*\d+\s*}/is

        $pattern = "/{$this->tag[0]}\s*{$find}\s*{$this->tag[1]}/is";
        $this->template = preg_replace(
            $pattern,
            $replace,
            $this->template
        );
    }

    //变量
    public function varExp()
    {
        $exp = '(\$[a-zA-Z_][0-9a-zA-Z_\'\"\[\]]*)';
        $replace = "<?php if(isset(\\1)){echo \\1;}?>";
        $this->_replace($exp, $replace);
    }

    //loop标签
    public function loopExp()
    {
        $exp = '(([a-zA-Z]+)\s*:\s*)?loop\s+(.*?)\s+in\s+(.*?)';
        $replace = "<?php
        if(is_array(\\4)){
           \$\\2_COUNT  = count(\\4);
        }
        \$\\2_INDEX = 0;
        foreach(\\4 as \\3){
            \$\\2_INDEX++;
      
        ?>";
        $this->_replace($exp, $replace);
    }

    //end loop
    public function endLoopExp()
    {
        $exp = '\/loop';
        $replace = '<?php } ?>';
        $this->_replace($exp, $replace);
    }

    //include
    public function includeExp($content = null, $parent = null)
    {
        $exp = 'include\s+(.*?)';
        $pattern = "/{$this->tag[0]}\s*{$exp}\s*{$this->tag[1]}/is";

        if ($content) {
            return preg_replace_callback(
                $pattern,
                function ($matches) use ($parent) {
                    return $this->includeTpl($matches[1], $parent);
                },
                $content
            );
        } else {
            $this->template = preg_replace_callback(
                $pattern,
                function ($matches) use ($parent) {
                    return $this->includeTpl($matches[1], $parent);
                },
                $this->template
            );
        }
    }

    protected function includeTpl($sub, $parent)
    {
        //模板递归调用判断
        if (isset($this->tpl_parents[$parent])) {
            $parent_path = $this->tpl_parents[$parent];
            $parent_path[] = $parent;
            //检查模板是否死循环调用
            if (in_array($sub, $parent_path)) {
                print_r($this->tpl_parents);
                die("{$parent}调用{$sub} 文件，产生死循环！");
            } else {
                $this->tpl_parents[$sub] = $parent_path;
            }
        } else {
            $this->tpl_parents[$sub] = [$parent];
        }

        return $this->readTpl($sub);

    }

    protected function readTpl($tpl)
    {

        //读取子模板
        $file = "{$this->base_dir}/{$tpl}{$this->suffix}";
        if (!file_exists($file)) {
            die("{$file}文件不存在，或者不可读取!");
        }
        $content = file_get_contents($file);

        return $this->includeExp($content, $tpl);
    }

    //extends 母版标签
    public function extendsExp()
    {
        $exp = 'extends\s+(.*?)';
        $pattern = "/{$this->tag[0]}\s*{$exp}\s*{$this->tag[1]}/is";
        $ismatch = preg_match($pattern, $this->template, $matches);
        if ($ismatch) {
            $master = $this->readTpl($matches[1]);
            $this->getTagBody($master);

            $exp_ph = '@(.*?)';
            $pattern_ph = "/{$this->tag[0]}\s*{$exp_ph}\s*{$this->tag[1]}/is";
            $this->template = preg_replace_callback($pattern_ph, [$this, 'replaceTag'], $master);
        }
    }

    private function replaceTag($matches)
    {
        $tag = $matches[1];
        return isset($this->tag_body[$tag]) ? $this->tag_body[$tag] : '';
    }

    private function getTagBody($master)
    {
        //place holder
        $exp = '@(.*?)';
        $pattern = "/{$this->tag[0]}\s*{$exp}\s*{$this->tag[1]}/is";
        preg_match_all($pattern, $master, $matches);

        $this->tag_body = [];
        foreach ($matches[1] as $ph) {
            //$ph=news_body    {news_body}(.*?){/news_body}
            $pattern_ph = "/{$this->tag[0]}\s*({$ph})\s*{$this->tag[1]}(.*?){$this->tag[0]}\s*\/{$ph}\s*{$this->tag[1]}/is";
            $ismatched = preg_match($pattern_ph, $this->template, $matches_ph);
            if ($ismatched) {
                $this->tag_body[$matches_ph[1]] = $matches_ph[2];
            }
        }



    }


}