PHP Expression Language based on TWIG
=====================================

This library provides interfaces for safe evaluate expressions, compile them to native PHP code for performance.

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
