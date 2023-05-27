<?php

declare(strict_types=1);

namespace Okvpn\Expression\Parser\Visitor;

use Okvpn\Expression\Parser\PrintToLoggerNode;
use Twig\Environment;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Node\TextNode;
use Twig\NodeVisitor\NodeVisitorInterface;

class LangNodeVisitor implements NodeVisitorInterface
{
    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof PrintNode) {
            return new PrintToLoggerNode($node->getNode('expr'), $node->getTemplateLine());
        }
        if ($node instanceof TextNode) {
            return new PrintToLoggerNode($node->getAttribute('data'), $node->getTemplateLine());
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    public function getPriority(): int
    {
        return 0;
    }
}
