<?php

declare(strict_types=1);

/**
 * Fail if pre-rename repo-owned camelCase identifiers remain in src/, tests/, or examples/.
 */

$scanRoots = [
    __DIR__ . '/../src',
    __DIR__ . '/../tests',
    __DIR__ . '/../examples',
];

$patterns = [
    '/\bAbstractLexer\b/' => 'AbstractLexer (use Abstract_Lexer)',
    '/\bclass\s+ConcreteLexer\b/' => 'ConcreteLexer class (use Concrete_Lexer)',
    '/\bclass\s+EnumLexer\b/' => 'EnumLexer class (use Enum_Lexer)',
    '/\bclass\s+MutableLexer\b/' => 'MutableLexer class (use Mutable_Lexer)',
    '/\bclass\s+AbstractLexerTest\b/' => 'AbstractLexerTest (use Abstract_Lexer_Test)',
    '/\bclass\s+TokenTest\b/' => 'TokenTest (use Token_Test)',
    '/\benum\s+TokenType\b/' => 'TokenType enum (use Token_Type)',
    '/\bUnit_Enum\b/' => 'Unit_Enum (use UnitEnum)',
    '/->setInput\s*\(/' => 'setInput() (use set_input())',
    '/->resetPeek\s*\(/' => 'resetPeek() (use reset_peek())',
    '/->resetPosition\s*\(/' => 'resetPosition() (use reset_position())',
    '/->getInputUntilPosition\s*\(/' => 'getInputUntilPosition() (use get_input_until_position())',
    '/->isNextToken\s*\(/' => 'isNextToken() (use is_next_token())',
    '/->isNextTokenAny\s*\(/' => 'isNextTokenAny() (use is_next_token_any())',
    '/->moveNext\s*\(/' => 'moveNext() (use move_next())',
    '/->skipUntil\s*\(/' => 'skipUntil() (use skip_until())',
    '/->isA\s*\(/' => 'isA() (use is_a())',
    '/->getLiteral\s*\(/' => 'getLiteral() (use get_literal())',
    '/function\s+getCatchablePatterns\s*\(/' => 'getCatchablePatterns() (use get_catchable_patterns())',
    '/function\s+getNonCatchablePatterns\s*\(/' => 'getNonCatchablePatterns() (use get_non_catchable_patterns())',
    '/function\s+getType\s*\(/' => 'getType() (use get_type())',
    '/->addCatchablePattern\s*\(/' => 'addCatchablePattern() (use add_catchable_pattern())',
    '/->get_constants\s*\(/' => 'get_constants() (use getConstants())',
    '/\$concreteLexer\b/' => '$concreteLexer (use $concrete_lexer)',
    '/\$enumLexer\b/' => '$enumLexer (use $enum_lexer)',
    '/\$mutableLexer\b/' => '$mutableLexer (use $mutable_lexer)',
];

$failures = [];

foreach ($scanRoots as $root) {
    if (! is_dir($root)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $fileInfo) {
        if (! $fileInfo instanceof SplFileInfo || ! $fileInfo->isFile()) {
            continue;
        }

        if ($fileInfo->getExtension() !== 'php') {
            continue;
        }

        $path = $fileInfo->getPathname();
        $contents = file_get_contents($path);
        if ($contents === false) {
            continue;
        }

        foreach ($patterns as $regex => $message) {
            if (preg_match($regex, $contents) === 1) {
                $failures[] = sprintf('%s: %s', $path, $message);
            }
        }
    }
}

if ($failures !== []) {
    fwrite(STDERR, "Legacy camelCase rename artifacts found:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '  ' . $failure . "\n");
    }

    exit(1);
}

fwrite(STDOUT, "check-no-legacy-camelcase: no legacy repo-owned camelCase names found.\n");
exit(0);
