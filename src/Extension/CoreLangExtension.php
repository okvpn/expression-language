<?php

declare(strict_types=1);

namespace Okvpn\Expression\Extension;

use Okvpn\Expression\Parser\ReturnTokenParser;
use Okvpn\Expression\Parser\SetTokenParser;
use Okvpn\Expression\Parser\Visitor\LangNodeVisitor;
use Twig\Extension\AbstractExtension;

class CoreLangExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getTokenParsers(): array
    {
        return [
            new ReturnTokenParser(),
            new SetTokenParser(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeVisitors(): array
    {
        return [
            new LangNodeVisitor(),
        ];
    }
}
