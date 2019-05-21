<?php
/**
 * Created by IntelliJ IDEA.
 * User: fanglexue
 * Date: 2019/5/21
 * Time: 2:19 PM
 */


namespace expression\parse;

use expression\parse\RuntimeError;

class Context
{
    protected $fnt = array(), $cst = array( 'PI' => M_PI, 'π' => M_PI );
    public function fn($name, array $args)
    {
        if (!isset($this->fnt[$name]))
            throw new RuntimeError(' 未定义方法 "' . $name . '"');

        return (float) call_user_func_array($this->fnt[$name], $args);
    }

    public function cs($name)
    {
        if (!isset($this->cst[$name]))
            throw new RuntimeError(' 未定义常数 "' . $name . '"');

        return $this->cst[$name];
    }

    public function def($name, $value = null)
    {
        if ($value === null) $value = $name;

        if (is_callable($value))
            $this->fnt[$name] = $value;

        elseif (is_numeric($value))
            $this->cst[$name] = (float) $value;

        else
            throw new Exception('未定义数据类型');
    }
}