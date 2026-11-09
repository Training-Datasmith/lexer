<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\Abstract_Lexer;
use Doctrine\Common\Lexer\Token;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_map;
use function assert;
use function count;
use function is_int;
use function is_numeric;
use function setlocale;

use const LC_ALL;

class Abstract_Lexer_Test extends TestCase
{
    private Concrete_Lexer $concrete_lexer;

    public function setUp(): void
    {
        $this->concrete_lexer = new Concrete_Lexer();
    }

    public function tearDown(): void
    {
        setlocale(LC_ALL, null);
    }

    /** @phpstan-return list<array{string, list<Token<string, string|int>>}> */
    public static function data_provider(): array
    {
        return [
            [
                'price=10',
                [
                    new Token('price', 'string', 0),
                    new Token('=', 'operator', 5),
                    new Token(10, 'int', 6),
                ],
            ],
        ];
    }

    public function test_reset_peek(): void
    {
        $expected_tokens = [
            new Token('price', 'string', 0),
            new Token('=', 'operator', 5),
            new Token(10, 'int', 6),
        ];

        $this->concrete_lexer->set_input('price=10');

        $this->assertEquals($expected_tokens[0], $this->concrete_lexer->peek());
        $this->assertEquals($expected_tokens[1], $this->concrete_lexer->peek());
        $this->concrete_lexer->reset_peek();
        $this->assertEquals($expected_tokens[0], $this->concrete_lexer->peek());
    }

    public function test_reset_position(): void
    {
        $expected_tokens = [
            new Token('price', 'string', 0),
            new Token('=', 'operator', 5),
            new Token(10, 'int', 6),
        ];

        $this->concrete_lexer->set_input('price=10');
        $this->assertNull($this->concrete_lexer->lookahead);

        $this->assertTrue($this->concrete_lexer->move_next());
        $this->assertEquals($expected_tokens[0], $this->concrete_lexer->lookahead);

        $this->assertTrue($this->concrete_lexer->move_next());
        $this->assertEquals($expected_tokens[1], $this->concrete_lexer->lookahead);

        $this->concrete_lexer->reset_position(0);

        $this->assertTrue($this->concrete_lexer->move_next());
        $this->assertEquals($expected_tokens[0], $this->concrete_lexer->lookahead);
    }

    /** @phpstan-param list<Token<string, string|int>>  $expected_tokens */
    #[DataProvider('data_provider')]
    public function test_move_next(string $input, array $expected_tokens): void
    {
        $this->concrete_lexer->set_input($input);
        $this->assertNull($this->concrete_lexer->lookahead);

        for ($i = 0; $i < count($expected_tokens); $i++) {
            $this->assertTrue($this->concrete_lexer->move_next());
            $this->assertEquals($expected_tokens[$i], $this->concrete_lexer->lookahead);
        }

        $this->assertFalse($this->concrete_lexer->move_next());
        $this->assertNull($this->concrete_lexer->lookahead);
    }

    public function test_skip_until(): void
    {
        $this->concrete_lexer->set_input('price=10');

        $this->assertTrue($this->concrete_lexer->move_next());
        $this->concrete_lexer->skip_until('operator');

        $this->assertEquals(
            new Token('=', 'operator', 5),
            $this->concrete_lexer->lookahead,
        );
    }

    public function test_utf8_mismatch(): void
    {
        $this->concrete_lexer->set_input("\xE9=10");

        $this->assertTrue($this->concrete_lexer->move_next());

        $this->assertEquals(
            new Token("\xE9=10", 'string', 0),
            $this->concrete_lexer->lookahead,
        );
    }

    /** @phpstan-param list<Token<string, string|int>> $expected_tokens */
    #[DataProvider('data_provider')]
    public function test_peek(string $input, array $expected_tokens): void
    {
        $this->concrete_lexer->set_input($input);
        foreach ($expected_tokens as $expected_token) {
            $actual_token = $this->concrete_lexer->peek();
            assert($actual_token !== null);
            $this->assertEquals($expected_token, $actual_token);
            $this->assertSame($expected_token->value, $actual_token->value);
            $this->assertSame($expected_token->type, $actual_token->type);
            $this->assertSame($expected_token->position, $actual_token->position);
        }

        $this->assertNull($this->concrete_lexer->peek());
    }

    /** @phpstan-param list<Token<string, string|int>> $expected_tokens */
    #[DataProvider('data_provider')]
    public function test_glimpse(string $input, array $expected_tokens): void
    {
        $this->concrete_lexer->set_input($input);

        foreach ($expected_tokens as $expected_token) {
            $actual_token = $this->concrete_lexer->glimpse();
            assert($actual_token !== null);
            $this->assertEquals($expected_token, $actual_token);
            $this->assertEquals($expected_token, $this->concrete_lexer->glimpse());
            $this->assertSame($expected_token->value, $actual_token->value);
            $this->assertSame($expected_token->type, $actual_token->type);
            $this->assertSame($expected_token->position, $actual_token->position);
            $this->concrete_lexer->move_next();
        }

        $this->assertNull($this->concrete_lexer->peek());
    }

    /** @phpstan-return list<array{string, int, string}> */
    public static function input_until_position_data_provider(): array
    {
        return [
            ['price=10', 5, 'price'],
        ];
    }

    #[DataProvider('input_until_position_data_provider')]
    public function test_get_input_until_position(
        string $input,
        int $position,
        string $expected_input,
    ): void {
        $this->concrete_lexer->set_input($input);

        $this->assertSame($expected_input, $this->concrete_lexer->get_input_until_position($position));
    }

    /** @phpstan-param list<Token<string, string|int>> $expected_tokens */
    #[DataProvider('data_provider')]
    public function test_is_next_token(string $input, array $expected_tokens): void
    {
        $this->concrete_lexer->set_input($input);

        $this->concrete_lexer->move_next();
        for ($i = 0; $i < count($expected_tokens); $i++) {
            assert($expected_tokens[$i]->type !== null);
            $this->assertTrue($this->concrete_lexer->is_next_token($expected_tokens[$i]->type));
            $this->concrete_lexer->move_next();
        }
    }

    /** @phpstan-param list<Token<string, string|int>> $expected_tokens */
    #[DataProvider('data_provider')]
    public function test_is_next_token_any(string $input, array $expected_tokens): void
    {
        $all_token_types = array_map(static function ($token): string {
            assert($token->type !== null);

            return $token->type;
        }, $expected_tokens);

        $this->concrete_lexer->set_input($input);

        $this->concrete_lexer->move_next();
        for ($i = 0; $i < count($expected_tokens); $i++) {
            assert($expected_tokens[$i]->type !== null);
            $this->assertTrue($this->concrete_lexer->is_next_token_any([$expected_tokens[$i]->type]));
            $this->assertTrue($this->concrete_lexer->is_next_token_any($all_token_types));
            $this->concrete_lexer->move_next();
        }
    }

    public function test_get_literal(): void
    {
        $this->assertSame('Doctrine\Tests\Common\Lexer\Concrete_Lexer::INT', $this->concrete_lexer->get_literal('int'));
        $this->assertSame('fake_token', $this->concrete_lexer->get_literal('fake_token'));
    }

    public function test_get_literal_with_enum_lexer(): void
    {
        $enum_lexer = new Enum_Lexer();
        $this->assertSame(
            'Doctrine\Tests\Common\Lexer\Token_Type::OPERATOR',
            $enum_lexer->get_literal(Token_Type::OPERATOR),
        );
    }

    public function test_is_a(): void
    {
        $this->assertTrue($this->concrete_lexer->is_a('11', 'int'));
        $this->assertTrue($this->concrete_lexer->is_a('1.1', 'int'));
        $this->assertTrue($this->concrete_lexer->is_a('=', 'operator'));
        $this->assertTrue($this->concrete_lexer->is_a('>', 'operator'));
        $this->assertTrue($this->concrete_lexer->is_a('<', 'operator'));
        $this->assertTrue($this->concrete_lexer->is_a('fake_text', 'string'));
    }

    public function test_add_catchable_patterns_to_mutable_lexer(): void
    {
        $mutable_lexer = new Mutable_Lexer();
        $mutable_lexer->add_catchable_pattern('[a-z]');
        $mutable_lexer->set_input('one');
        $token = $mutable_lexer->glimpse();

        $this->assertNotNull($token);
        $this->assertEquals('o', $token->value);

        $mutable_lexer = new Mutable_Lexer();
        $mutable_lexer->add_catchable_pattern('[a-z]+');
        $mutable_lexer->set_input('one');
        $token = $mutable_lexer->glimpse();

        $this->assertNotNull($token);
        $this->assertEquals('one', $token->value);
    }

    public function test_marker_annotation_locale_tr(): void
    {
        setlocale(LC_ALL, 'tr_TR.utf8', 'tr_TR');
        $mutable_lexer = new Mutable_Lexer();
        $mutable_lexer->add_catchable_pattern('[a-z_\\\][a-z0-9_\:\\\]*[a-z_][a-z0-9_]*');
        $mutable_lexer->add_catchable_pattern('(?:[+-]?[0-9]+(?:[\.][0-9]+)*)(?:[eE][+-]?[0-9]+)?');
        $mutable_lexer->add_catchable_pattern('"(?:""|[^"])*+"');
        $mutable_lexer->set_input('@ODM\Id');

        self::assertNull($mutable_lexer->token);
        self::assertNull($mutable_lexer->lookahead);
        self::assertTrue($mutable_lexer->move_next());
        self::assertNull($mutable_lexer->token);
        self::assertNotNull($mutable_lexer->lookahead);
        self::assertEquals('@', $mutable_lexer->lookahead->value);
        self::assertTrue($mutable_lexer->move_next());
        self::assertNotNull($mutable_lexer->token);
        self::assertEquals('@', $mutable_lexer->token->value);
        self::assertEquals('ODM\Id', $mutable_lexer->lookahead->value);
    }

    public function test_can_tokenize_float_value(): void
    {
        $lexer = new /** @template-extends Abstract_Lexer<int, string|int|float|bool> */ class () extends Abstract_Lexer {
            final public const T_NONE    = 1;
            final public const T_INTEGER = 2;
            final public const T_FLOAT   = 4;
            final public const T_BOOL    = 8;

            protected function get_type(string|int|float|bool &$value): int
            {
                if ($value === 'y') {
                    $value = true;

                    return self::T_BOOL;
                }

                if (is_numeric($value)) {
                    $value += 0;

                    if (is_int($value)) {
                        return self::T_INTEGER;
                    }

                    return self::T_FLOAT;
                }

                return self::T_NONE;
            }

            /** {@inheritDoc} */
            protected function get_catchable_patterns(): array
            {
                return [
                    '(?:[0-9]+)(?:[\.][0-9]+)?(?:e[+-]?[0-9]+)?',
                    'y',
                ];
            }

            /** {@inheritDoc} */
            protected function get_non_catchable_patterns(): array
            {
                return ['\s+'];
            }
        };

        $lexer->set_input('123.456');
        $token = $lexer->peek();
        assert($token !== null);
        self::assertSame(123.456, $token->value, 'expect a real float, not a numerical string');

        $lexer->set_input('y');
        $token = $lexer->peek();
        assert($token !== null);
        self::assertTrue($token->value, 'expect a real bool, not a numerical string');
    }
}
