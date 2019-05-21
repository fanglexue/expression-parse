<?php
/**
 * Created by IntelliJ IDEA.
 * User: fanglexue
 * Date: 2019/5/21
 * Time: 12:02 PM
 */


namespace expression\parse;

use expression\parse\exception\ParseError;

class Myvar {

    public $vars = [];
    public $_var = [];

    const PATTEN = '/\{#\w{1,}\}/';

    public function __construct(){}

    //转换字符串表达式变量
    public function _replace($subject){
        return preg_replace_callback(self::PATTEN,
            function($var){
                $var = $this->_cvar($var[0]);
                if(isset($this->_var[$var])){
                    return $this->_var[$var];
                }
                throw new ParseError('请注册变量');
            }
            ,$subject
        );
    }


    /**获取字符串中所有变量**/
    public function getVars($subject){
        preg_match_all(self::PATTEN,$subject,$out,PREG_SET_ORDER);
        return $out;
    }

    /*
     * 注册变量
     */

    public function assign($data, $value = ''){
        if(is_array($data)){
            foreach($data as $key => $val){
                if ($key != ''){
                    $this->_var[$key] = $val;
                }
            }
        }else{
            if($data != ''){
                $this->_var[$data] = $value;
            }
        }
    }


    /*
     *  获取变量$key
    */
    public function _cvar($var) {
        $sort_char = ['{','#'];
        $check_char_len = 2; //
        for($i=0; $i< $check_char_len; $i++){
            //按照顺序检查变量
            if($var[0] != $sort_char[$i]){
                throw new ParseError('变量解析错误');
            }
            $var = substr($var,1,strlen($var));
        }

        return  $var = trim(substr($var,0,-1));
    }

}