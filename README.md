#运算解析 

##Quick Start

###Installation

compposer require expression/parse:1.0.8

composer intall expression/parse

###Excample

```
use expression\parse\Parser;
use expression\parse\VarParse;
use expression\parse\Context;


$vp = new VarParse();
$exp = "{#dbz} + {#ts} * 3 + 2 * 5 + {@bar}({#sg},{#tz})";
$vp->assign('dbz',1);
$vp->assign("ts",4);
$vp->assign("sg",3.3);
$vp->assign("tz",3.4);

$ctx = new Context;
$ctx->def('bar', function($a, $b) { return $a * $b; });

$exp = $vp->_replace($exp);
print_r(Parser::parse($exp, $ctx));

```
