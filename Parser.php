<?php

/**
 * Created by IntelliJ IDEA.
 * User: fanglexue
 * Date: 2019/5/21
 * Time: 2:23 PM
 */


namespace expression\parse;

use expression\parse\Token;
use expression\parse\Context;
use expression\parse\Scanner;

use expression\parse\exception\ParseError;
use expression\parse\exception\RuntimeError;

const T_NUMBER      = 1,  // 
      T_IDENT       = 2,  // 
      T_FUNCTION    = 4,  // 
      T_POPEN       = 8,  // (
      T_PCLOSE      = 16,  // )
      T_COMMA       = 32, // ,
      T_OPERATOR    = 64, // 
      T_PLUS        = 65, // +
      T_MINUS       = 66, // -
      T_TIMES       = 67, // *
      T_DIV         = 68, // /
      T_MOD         = 69, // %
      T_POW         = 70, // ^
      T_UNARY_PLUS  = 71, // + 
      T_UNARY_MINUS = 72, // - 
      T_NOT         = 73; // ! 

class Parser
{
    const ST_1 = 1, // 等待一元操作数
        ST_2 = 2; // 等待操作

    protected $scanner, $state = self::ST_1;
    protected $queue, $stack;

    public function __construct(Scanner $scanner)
    {
        $this->scanner = $scanner;

        //队列
        $this->queue = array();
        $this->stack = array();

        while (($t = $this->scanner->next()) !== false)
            $this->handle($t);

        while ($t = array_pop($this->stack)) {
            if ($t->type === T_POPEN || $t->type === T_PCLOSE)
                throw new ParseError('`(`,`)` 不匹配');

            $this->queue[] = $t;
        }
    }

    public function reduce(Context $ctx)
    {
        $this->stack = array();
        $len = 0;

        while ($t = array_shift($this->queue)) {
            switch ($t->type) {
                case T_NUMBER:
                case T_IDENT:
                    if ($t->type === T_IDENT)
                        $t = new Token(T_NUMBER, $ctx->cs($t->value));

                    $this->stack[] = $t;
                    ++$len;
                    break;

                case T_PLUS:
                case T_MINUS:
                case T_UNARY_PLUS:
                case T_UNARY_MINUS:
                case T_TIMES:
                case T_DIV:
                case T_MOD:
                case T_POW:
                case T_NOT:
                    $na = $this->argc($t);

                    if ($len < $na)
                        throw new RuntimeError(' 参数缺失 "' . $t->value . '" (' . $na . ' -> ' . $len . ')');

                    $rhs = array_pop($this->stack);
                    $lhs = null;

                    if ($na > 1)
                        $lhs = array_pop($this->stack);


                    $len -= $na - 1;

                    $this->stack[] = new Token(T_NUMBER, $this->op($t->type, $lhs, $rhs));
                    break;

                case T_FUNCTION:
                    $argc = $t->argc;
                    $argv = array();
                    $len -= $argc - 1;

                    for (; $argc > 0; --$argc)
                        array_unshift($argv, array_pop($this->stack)->value);
                    $this->stack[] = new Token(T_NUMBER, $ctx->fn($t->value, $argv));
                    break;

                default:
                    throw new RuntimeError('解析错误不存在的token `' . $t->value . '`');
            }
        }

        if (count($this->stack) === 1)
            return array_pop($this->stack)->value;

        throw new RuntimeError('运行时错误：栈内堆积');
    }

    protected function op($op, $lhs, $rhs)
    {
        if ($lhs !== null) {
            $lhs = $lhs->value;
            $rhs = $rhs->value;

            switch ($op) {
                case T_PLUS:
                    return $lhs + $rhs;

                case T_MINUS:
                    return $lhs - $rhs;

                case T_TIMES:
                    return $lhs * $rhs;

                case T_DIV:
                    if ($rhs === 0.)
                        throw new RuntimeError('运行时异常');

                    return $lhs / $rhs;

                case T_MOD:
                    if ($rhs === 0.)
                        throw new RuntimeError('运行时异常');

                    return (float)$lhs % $rhs;

                case T_POW:
                    return (float)pow($lhs, $rhs);
            }

            // throw?
            return 0;
        }

        switch ($op) {
            case T_NOT:
                return (float)!$rhs->value;

            case T_UNARY_MINUS:
                return -$rhs->value;

            case T_UNARY_PLUS:
                return +$rhs->value;
        }
    }

    protected function argc(Token $t)
    {
        switch ($t->type) {
            case T_PLUS:
            case T_MINUS:
            case T_TIMES:
            case T_DIV:
            case T_MOD:
            case T_POW:
                return 2;
        }

        return 1;
    }

    public function dump($str = false)
    {
        if ($str === false) {
            print_r($this->queue);
            return;
        }
        $res = array();
        foreach ($this->queue as $t) {
            $val = $t->value;

            switch ($t->type) {
                case T_UNARY_MINUS:
                case T_UNARY_PLUS:
                    $val = 'unary' . $val;
                    break;
            }

            $res[] = $val;
        }

        print implode(' ', $res);
    }

    protected function fargs($fn)
    {
        $this->handle($this->scanner->next()); // '('

        $argc = 0;
        $next = $this->scanner->peek();

        if ($next && $next->type !== T_PCLOSE) {
            $argc = 1;

            while ($t = $this->scanner->next()) {
                $this->handle($t);

                if ($t->type === T_PCLOSE)
                    break;

                if ($t->type === T_COMMA)
                    ++$argc;
            }
        }

        $fn->argc = $argc;
    }

    protected function handle(Token $t)
    {
        switch ($t->type) {
            case T_NUMBER:
            case T_IDENT:
                $this->queue[] = $t;
                $this->state = self::ST_2;
                break;

            case T_FUNCTION:
                $this->stack[] = $t;
                $this->fargs($t);
                break;


            case T_COMMA:

                $pe = false;

                while ($t = end($this->stack)) {
                    if ($t->type === T_POPEN) {
                        $pe = true;
                        break;
                    }

                    $this->queue[] = array_pop($this->stack);
                }

                if ($pe !== true)
                    throw new ParseError(' 丢失 `(` 和 `,`');

                break;

            case T_PLUS:
            case T_MINUS:
            case T_UNARY_PLUS:
            case T_UNARY_MINUS:
            case T_TIMES:
            case T_DIV:
            case T_MOD:
            case T_POW:
            case T_NOT:
                while (!empty($this->stack)) {
                    $s = end($this->stack);

                    switch ($s->type) {
                        default:
                            break 2;

                        case T_PLUS:
                        case T_MINUS:
                        case T_UNARY_PLUS:
                        case T_UNARY_MINUS:
                        case T_TIMES:
                        case T_DIV:
                        case T_MOD:
                        case T_POW:
                        case T_NOT:
                            $p1 = $this->preced($t);
                            $p2 = $this->preced($s);

                            if (!(($this->assoc($t) === 1 && ($p1 <= $p2)) || ($p1 < $p2)))
                                break 2;

                            $this->queue[] = array_pop($this->stack);
                    }
                }

                $this->stack[] = $t;
                $this->state = self::ST_1;
                break;

            case T_POPEN:
                $this->stack[] = $t;
                $this->state = self::ST_1;
                break;

            case T_PCLOSE:
                $pe = false;

                while ($t = array_pop($this->stack)) {
                    if ($t->type === T_POPEN) {
                        $pe = true;
                        break;
                    }

                    $this->queue[] = $t;
                }

                if ($pe !== true)
                    throw new ParseError(' 解析错误  `)`');

                if (($t = end($this->stack)) && $t->type === T_FUNCTION)
                    $this->queue[] = array_pop($this->stack);

                $this->state = self::ST_2;
                break;

            default:
                throw new ParseError(' 解析错误 "' . $t->value . '"');
        }
    }

    protected function assoc(Token $t)
    {
        switch ($t->type) {
            case T_TIMES:
            case T_DIV:
            case T_MOD:

            case T_PLUS:
            case T_MINUS:
                return 1; //ltr

            case T_NOT:
            case T_UNARY_PLUS:
            case T_UNARY_MINUS:

            case T_POW:
                return 2; //rtl
        }

        return 0; //nassoc
    }

    protected function preced(Token $t)
    {
        switch ($t->type) {
            case T_NOT:
            case T_UNARY_PLUS:
            case T_UNARY_MINUS:
                return 4;

            case T_POW:
                return 3;

            case T_TIMES:
            case T_DIV:
            case T_MOD:
                return 2;

            case T_PLUS:
            case T_MINUS:
                return 1;
        }

        return 0;
    }

    public static function parse($term, Context $ctx = null)
    {
        $obj = new self(new Scanner($term));
        return $obj
            ->reduce($ctx ?: new Context);
    }
}
