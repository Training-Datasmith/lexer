<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\Abstract_Lexer;

/** @extends Abstract_Lexer<int, string> */
class Mutable_Lexer extends Abstract_Lexer
{
    /** @var string[] */
    private array $catchable_patterns = [];

    public function add_catchable_pattern(string $pattern): void
    {
        $this->catchable_patterns[] = $pattern;
    }

    /**
     * {@inheritDoc}
     */
    protected function get_catchable_patterns(): array
    {
        return $this->catchable_patterns;
    }

    /**
     * {@inheritDoc}
     */
    protected function get_non_catchable_patterns(): array
    {
        return ['[\s,]+'];
    }

    protected function get_type(string &$value): int
    {
        return 1;
    }
}
