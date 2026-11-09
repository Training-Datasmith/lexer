<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\Token;
use PHPUnit\Framework\TestCase;

final class Token_Test extends TestCase
{
    public function test_is_a(): void
    {
        /** @var Token<'string'|'int', string> $token */
        $token = new Token('foo', 'string', 1);

        self::assertTrue($token->is_a('string'));
        self::assertTrue($token->is_a('int', 'string'));
        self::assertFalse($token->is_a('int'));
    }
}
