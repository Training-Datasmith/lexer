<?php

declare(strict_types=1);

/**
 * Example: building a simple arithmetic lexer using Doctrine Abstract_Lexer.
 *
 * Run from the lexer project root:
 *   php examples/custom_lexer.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Doctrine\Common\Lexer\Abstract_Lexer;

/**
 * A minimal lexer for a simple arithmetic DSL: numbers, +, -, *, /, and identifiers.
 */
class Arithmetic_Lexer extends Abstract_Lexer
{
    public const T_NONE       = 'T_NONE';
    public const T_NUMBER     = 'T_NUMBER';
    public const T_PLUS       = 'T_PLUS';
    public const T_MINUS      = 'T_MINUS';
    public const T_MULTIPLY   = 'T_MULTIPLY';
    public const T_DIVIDE     = 'T_DIVIDE';
    public const T_IDENTIFIER = 'T_IDENTIFIER';

    protected function get_catchable_patterns(): array
    {
        return [
            '[0-9]+(?:\.[0-9]+)?',
            '[a-zA-Z_][a-zA-Z0-9_]*',
            '[+\-*/]',
        ];
    }

    protected function get_non_catchable_patterns(): array
    {
        return ['\s+'];
    }

    protected function get_type(&$value): string
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

$lexer = new Arithmetic_Lexer();
$lexer->set_input('x + 3.14 * y - 1');

while ($lexer->move_next()) {
    $token = $lexer->token;
    printf("Type: %-12s  Value: %s\n", (string) $token->type, (string) $token->value);
}
