PHP Expression Language based on TWIG
=====================================

This library provides interfaces for safe evaluate expressions, compile them to native PHP code for performance.

[![Tests](https://github.com/okvpn/expression-language/actions/workflows/tests.yml/badge.svg)](https://github.com/okvpn/expression-language/actions/workflows/tests.yml)
[![Latest Stable Version](http://poser.pugx.org/okvpn/expression-language/v)](https://packagist.org/packages/okvpn/expression-language)
[![PHP Version Require](http://poser.pugx.org/okvpn/expression-language/require/php)](https://packagist.org/packages/okvpn/expression-language)

## Purpose
It is an alternative to the Symfony Expression Language which allows more operations such as `for` `if` etc.
You can to implement any logic and execute it safely and quickly. We removed `PRINT_TOKEN` `TEXT_TOKEN` from 
TWIG AST-tree to prevent output, added `return` statement and made a more improvement. 

## Features

- Allow to execution scripts and evaluate expressions.
- Debugging with PhpStorm twig debugger feature.
- Support all twig features, like [sandbox](https://twig.symfony.com/doc/3.x/api.html#sandbox-extension), custom extension, tokens, functions.
- Minimum performance overhead - faster that twig. 
- Syntax highlighting is already built into the most popular IDEs.

## Example Usage

Base evaluate Example

```php
$lang = new TwigLanguage(options: ['cache' => __DIR__ . '/var/cache/twig']);
$context = ['var1' => 10, 'users' => [1, 2, 5], 'user' => 1];

$lang->evaluate('user in users ? users|length + 1 : var1')
```

### Execute Script

```twig
{% set msg = 'New sms' %}
{% set newUsers = '' %}
{% set usersList = [] %}
{% set lastUserId = redis_get('sms-user') %} # redis_get - custom function

{% for user in users %}
    {% if user > lastUserId %}
        {% set newUsers = newUsers ~ user ~ ' ' %}
        {% do redis_set('sms-user', user, 0) %}
        {% set usersList[_key] = user %} # allow set to array 
    {% endif %}
{% endfor %}

{% if newUsers is not empty %}
    {% do telegram_send('chart111', msg ~ "\n" ~ newUsers|trim) %} # telegram_send - custom function
{% endif %}

{% return usersList %} # new return token 
```

```php
$lang = new TwigLanguage();
$script = <<<TXT
...
{% return 10 + 15 %}
TXT;

var_dump($lang->execute($script, $context));

// execute with template 
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/scripts');
$lang = new TwigLanguage(loader: $loader);
var_dump($lang->execute('test22.twig', $context));
```

`evaluate` method - is faster than `execute`, because we skip additional exception handling and error logic. 
Also `evaluate` - accept code without `{%` token.


## Benchmark Test

We use next the test to compare overhead between Symfony Expression Language and this library

```php
$lang = new TwigLanguage(options: [
    'cache' => __DIR__ . '/var/cache/twig',
    'auto_reload' => true,
]);


$expr = 'user == 1 ? var1 + var2 : var1 + 1';
$lang->evaluate($expr, $context);

// Symfony EL 
$sf = new ExpressionLanguage();
$expression = $sf->parse($expr, array_keys($context));
$sf->evaluate($expression, $context);

// Native PHP

$fn = static function ($context) {
    return $context['user'] === 1 ? $context['var1'] + $context['var2'] : $context['var1'] + 1;
};

$t1 = microtime(true);
for ($i = 0; $i < 200000; $i++) {
    // comment line where needed
    $result = $fn($context);
    $result = $sf->evaluate($expression, $context);
    $result = $lang->evaluate($expr, $context);
}

echo (microtime(true) - $t1)/200000 * 1000000 . "\n";
```

### Extends and sandbox mode.

See [Twig documentation](https://twig.symfony.com/doc/3.x/api.html)

#### Result

| Lang       | Op. Time | Overhead cost |
|------------|----------|---------------|
| Native PHP | 0.058µs  | 0%            |
| Symfony EL | 0.461µs  | +800%         |
| Twig lang  | 0.187µs  | +300%         |

License
=======

MIT License.
