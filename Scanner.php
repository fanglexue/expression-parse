<?php
/**
 * Created by IntelliJ IDEA.
 * User: fanglexue
 * Date: 2019/5/21
 * Time: 2:39 PM
 */

namespace  expression\parse;

use expression\parse\exception\SyntaxError;

class Scanner
{
    //                  运算符                   数值                       单词
    const PATTERN = '/^([!,\+\-\*\/\^%\(\)]|\d*\.\d+|\d+\.\d*|\d+|[a-z_A-Zπ]+[a-z_A-Z0-9]*|[ \t]+)/';

    const ERR_EMPTY = '空数据匹配: `%s`',
        ERR_MATCH = '语法错误 `%s`';
    protected $tokens = array( 0 );
    protected $lookup = array(
        '+' => T_PLUS,
        '-' => T_MINUS,
        '/' => T_DIV,
        '%' => T_MOD,
        '^' => T_POW,
        '*' => T_TIMES,
        '(' => T_POPEN,
        ')' => T_PCLOSE,
        '!' => T_NOT,
        ',' => T_COMMA
    );
    public function __construct($input)
    {
        $prev = new Token(T_OPERATOR, 'noop');

        while (trim($input) !== '') {
            if (!preg_match(self::PATTERN, $input, $match)) {
                throw new SyntaxError(sprintf(self::ERR_MATCH, substr($input, 0, 10)));
            }

            if (empty($match[1]) && $match[1] !== '0') {
                throw new SyntaxError(sprintf(self::ERR_EMPTY, substr($input, 0, 10)));
            }

            $input = substr($input, strlen($match[1]));

            if (($value = trim($match[1])) === '') {
                continue;
            }

            if (is_numeric($value)) {
                if ($prev->type === T_PCLOSE)
                    $this->tokens[] = new Token(T_TIMES, '*');

                $this->tokens[] = $prev = new Token(T_NUMBER, (float) $value);
                continue;
            }

            switch ($type = isset($this->lookup[$value]) ? $this->lookup[$value] : T_IDENT) {
                case T_PLUS:
                    if ($prev->type & T_OPERATOR || $prev->type == T_POPEN) $type = T_UNARY_PLUS;
                    break;

                case T_MINUS:
                    if ($prev->type & T_OPERATOR || $prev->type == T_POPEN) $type = T_UNARY_MINUS;
                    break;

                case T_POPEN:
                    switch ($prev->type) {
                        case T_IDENT:
                            $prev->type = T_FUNCTION;
                            break;

                        case T_NUMBER:
                        case T_PCLOSE:
                            // 2(2) -> 2 * 2 | (2)(2) -> 2 * 2
                            $this->tokens[] = new Token(T_TIMES, '*');
                            break;
                    }

                    break;
            }

            $this->tokens[] = $prev = new Token($type, $value);
        }
    }

    public function curr() { return current($this->tokens); }
    public function next() { return next($this->tokens); }
    public function prev() { return prev($this->tokens); }
    public function dump() { print_r($this->tokens); }

    public function peek()
    {
        $v = next($this->tokens);
        prev($this->tokens);

        return $v;
    }
}