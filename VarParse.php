<?php
/**
 * Created by IntelliJ IDEA.
 * User: fanglexue
 * Date: 2019/5/21
 * Time: 12:02 PM
 */


namespace expression\parse;

use expression\parse\exception\ParseError;

class VarParse {

    public $vars = [];

    private $fns = [];

    public $_var = [];


    const  PATTEN = '/(?:\{#\w{1,}\}?)|(?:\{@\w{1,}\}?)/';

    public function __construct(){}

    //转换字符串表达式变量
    public function _replace($subject){
        return preg_replace_callback(self::PATTEN,
            function($var){
                $type = $this->getVarType($var[0]); 
                $var = $this->getKey($var[0]); 
                if($type == 1){ 
                    if(isset($this->_var[$var])){
                         return $this->_var[$var];
                    }
                    throw new ParseError('please register variables '. $var .'!');
               }else{
                   return $var;
               }
            }
            ,$subject
        );
    }


    /**
     * Get all variables from a string 
     */
    public function getPatten($subject){
        preg_match_all(self::PATTEN,$subject,$out,PREG_SET_ORDER);
        foreach($out as $k => $v){
            $type = $this->getVarType($v[0]);         
            $vl = $this->getKey($v[0]); 
            if($type == 1){
                array_push($this->vars, $vl); 
            }else{
                array_push($this->fns, $vl); 
            }        
        }
        return $out; 
    } 


    public function getVars(){
        return $this->vars;
    }
    
    public function getFns(){
        return $this->fns;
    }


    /*
     * register variables _cvar
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



    /**
     * Identify whether the matched string is a variable or a function  
     *
     * return int   1: variable  2: function
     */
    private function getVarType($var){
        if($var[1] != '#' &&  $var[1] != '@'){
            throw new ParseError("Parse error");
        }
        return  $r = $var[1] == '#' ? 1 : 2;
    }
    

    /*
     *  Get key 
    */
   private  function getKey($var) {
        // Check variables in order .
        if($var[0] != '{' ||  ($var[1] != '@' && $var[1] != '#')){
            throw new ParseError('Parse error');
        }

        $var = substr($var,2,strlen($var));
        $var = trim(substr($var,0,-1));
        return  $var;
    }

}
