<?php

declare(strict_types=1);

namespace Okvpn\Expression\Parser;

use Twig\Compiler;
use Twig\Node\Node;

class ReturnNode extends Node
{
    public function __construct(Node $values, int $lineno, ?string $tag = null)
    {
        parent::__construct(['values' => $values], [], $lineno, $tag);
    }

    /**
     * {@inheritdoc}
     */
    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);
        $compiler->write('return ');

        if (\count($this->getNode('values')) > 1) {
            $compiler->raw('[');
            foreach ($this->getNode('values') as $idx => $node) {
                if ($idx) {
                    $compiler->raw(', ');
                }

                $compiler->subcompile($node);
            }
            $compiler->raw(']');
        } else {
            $compiler->subcompile($this->getNode('values'));
        }

        $compiler->raw(";\n");
    }
}
