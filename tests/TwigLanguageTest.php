<?php

declare(strict_types=1);

namespace Okvpn\Expression\Tests;

use Okvpn\Expression\TwigLanguage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TwigLanguageTest extends TestCase
{
    protected static $context = ['a' => 1, 'b' => 5, 'user' => 1, 'users' => [1, 5, 3]];

    #[DataProvider('expressionProvider')]
    public function testExpressionEval(string $expr, mixed $expected): void
    {
        $lang = new TwigLanguage();
        $result = $lang->evaluate($expr, self::$context);
        self::assertEquals($expected, $result);

        // Test cached loading
        $result = $lang->evaluate($expr, self::$context);
        self::assertEquals($expected, $result);
    }

    #[DataProvider('scriptProvider')]
    public function testExecuteFileSystemStorageScript(string $template, mixed $expected): void
    {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/scripts');

        $lang = new TwigLanguage(loader: $loader);
        $result = $lang->execute($template, self::$context);
        self::assertEquals($expected, $result);
    }

    public static function expressionProvider(): iterable
    {
        return [
            ['a + b', 6],
            ['a ? a + b +1 : 0', 7],
            ['user in users', true],
        ];
    }

    public static function scriptProvider(): iterable
    {
        return [
            ['test21.twig', 6],
            ['test22.twig', [1, 5,]],
        ];
    }
}
