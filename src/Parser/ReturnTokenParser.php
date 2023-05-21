<?php

declare(strict_types=1);

namespace Okvpn\Expression\Parser;

use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class ReturnTokenParser extends AbstractTokenParser
{
    /**
     * {@inheritdoc}
     */
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $expr = $this->parser->getExpressionParser();

        $values = $expr->parseMultitargetExpression();

        $stream->expect(/* Token::BLOCK_END_TYPE */ 3);

        return new ReturnNode($values, $lineno, $this->getTag());
    }

    /**
     * {@inheritdoc}
     */
    public function getTag(): string
    {
        return 'return';
    }
}
