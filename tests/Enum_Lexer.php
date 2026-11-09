<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\Abstract_Lexer;

use function in_array;
use function is_numeric;

/** @extends Abstract_Lexer<Token_Type, string|int> */
class Enum_Lexer extends Abstract_Lexer
{
    /**
     * {@inheritDoc}
     */
    protected function get_catchable_patterns(): array
    {
        return [
            '=|<|>',
            '[a-z]+',
            '\d+',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function get_non_catchable_patterns(): array
    {
        return [
            '\s+',
            '(.)',
        ];
    }

    protected function get_type(string &$value): Token_Type
    {
        if (is_numeric($value)) {
            $value = (int) $value;

            return Token_Type::INT;
        }

        if (in_array($value, ['=', '<', '>'])) {
            return Token_Type::OPERATOR;
        }

        return Token_Type::STRING;
    }
}
