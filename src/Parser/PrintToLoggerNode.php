<?php

declare(strict_types=1);

namespace Okvpn\Expression\Parser;

use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

class PrintToLoggerNode extends Node
{
    public function __construct($expr, int $lineno, string $tag = null)
    {
        if ($expr instanceof AbstractExpression) {
            parent::__construct(['expr' => $expr], [], $lineno, $tag);
        } else {
            parent::__construct([], ['data' => $expr], $lineno, $tag);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function compile(Compiler $compiler): void
    {
        if ($this->hasAttribute('data')) {
            $compiler
                ->addDebugInfo($this)
                ->write('$this->log(')
                ->string($this->getAttribute('data'))
                ->raw(', ' . $this->lineno)
                ->raw(");\n")
            ;
        } else {
            $compiler
                ->addDebugInfo($this)
                ->write('$this->log(')
                ->subcompile($this->getNode('expr'))
                ->raw(', ' . $this->lineno)
                ->raw(");\n")
            ;
        }
    }
}
