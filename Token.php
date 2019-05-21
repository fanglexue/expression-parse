<?php
/**
 * Created by IntelliJ IDEA.
 * User: fanglexue
 * Date: 2019/5/21
 * Time: 2:18 PM
 */

namespace expression\parse;

class Token
{
    public $type, $value, $argc = 0;

    public function __construct($type, $value)
    {
        $this->type  = $type;
        $this->value = $value;
    }
}