# 运算解析 

## 快速开始 

### 安装 

```
composer config repositories.expression/parse vcs https://github.com/fanglexue/expression-parse
```

```
composer require expression/parse:1.0.8
```


### 示例 



```
vim index.php

require(vendor/autoload.php);
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

php -f index.php

```

### 使用场景
可配置后台运算、模板化后台运算


