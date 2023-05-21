<?php

declare(strict_types=1);

namespace Okvpn\Expression\Parser;

use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class SetTokenParser extends AbstractTokenParser
{
    /**
     * {@inheritdoc}
     */
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $expr = $this->parser->getExpressionParser();
        $names = $this->parser->getExpressionParser()->parseAssignmentExpression();

        $arrayAccess = [];
        while ($stream->nextIf(/* Token::OPERATOR_TYPE */ 9, '[')) {
            $arrayAccess[] = $expr->parsePrimaryExpression();
            $stream->expect(/* Token::BLOCK_END_TYPE */ 9, ']');
        }

        $capture = false;
        if ($stream->nextIf(/* Token::OPERATOR_TYPE */ 8, '=')) {
            $values = $expr->parseMultitargetExpression();

            $stream->expect(/* Token::BLOCK_END_TYPE */ 3);

            if (\count($names) !== \count($values)) {
                throw new SyntaxError('When using set, you must have the same number of variables and assignments.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
        } else {
            $capture = true;

            if (\count($names) > 1) {
                throw new SyntaxError('When using set with a block, you cannot have a multi-target.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }

            $stream->expect(/* Token::BLOCK_END_TYPE */ 3);

            $values = $this->parser->subparse([$this, 'decideBlockEnd'], true);
            $stream->expect(/* Token::BLOCK_END_TYPE */ 3);
        }

        return new SetNode($capture, $names, $values, $lineno, $arrayAccess, $this->getTag());
    }

    public function decideBlockEnd(Token $token): bool
    {
        return $token->test('endset');
    }

    /**
     * {@inheritdoc}
     */
    public function getTag(): string
    {
        return 'set';
    }
}
