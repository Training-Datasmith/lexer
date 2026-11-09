<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\Abstract_Lexer;

use function in_array;
use function is_numeric;

/** @extends Abstract_Lexer<string, string|int> */
class Concrete_Lexer extends Abstract_Lexer
{
    final public const INT = 'int';

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

    protected function get_type(string|int|float &$value): string
    {
        if (is_numeric($value)) {
            $value = (int) $value;

            return 'int';
        }

        if (in_array($value, ['=', '<', '>'])) {
            return 'operator';
        }

        return 'string';
    }
}
