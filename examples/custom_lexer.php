<?php

declare(strict_types=1);

/**
 * Example: building a simple SQL-like lexer using Doctrine AbstractLexer.
 *
 * Run from the lexer project root:
 *   php examples/custom_lexer.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Doctrine\Common\Lexer\AbstractLexer;

/**
 * A minimal lexer for a simple arithmetic DSL: numbers, +, -, *, /, and identifiers.
 */
class ArithmeticLexer extends AbstractLexer
{
    public const T_NONE       = 'T_NONE';
    public const T_NUMBER     = 'T_NUMBER';
    public const T_PLUS       = 'T_PLUS';
    public const T_MINUS      = 'T_MINUS';
    public const T_MULTIPLY   = 'T_MULTIPLY';
    public const T_DIVIDE     = 'T_DIVIDE';
    public const T_IDENTIFIER = 'T_IDENTIFIER';

    protected function getCatchablePatterns(): array
    {
        return [
            '[0-9]+(?:\.[0-9]+)?', // numbers
            '[a-zA-Z_][a-zA-Z0-9_]*', // identifiers
            '[+\-*/]', // operators
        ];
    }

    protected function getNonCatchablePatterns(): array
    {
        return ['\s+']; // whitespace
    }

    protected function getType(&$value): string
    {
        if (is_numeric($value)) {
            return self::T_NUMBER;
        }
        return match ($value) {
            '+'     => self::T_PLUS,
            '-'     => self::T_MINUS,
            '*'     => self::T_MULTIPLY,
            '/'     => self::T_DIVIDE,
            default => self::T_IDENTIFIER,
        };
    }
}

$lexer = new ArithmeticLexer();
$lexer->setInput('x + 3.14 * y - 1');

while ($lexer->moveNext()) {
    $token = $lexer->token;
    printf("Type: %-12s  Value: %s\n", $token->type, $token->value);
}
