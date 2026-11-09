Introduction
============

Doctrine Lexer is a library that can be used in Top-Down, Recursive
Descent Parsers. This lexer is used in Doctrine Annotations and in
Doctrine ORM (DQL).

To write your own parser you just need to extend ``Doctrine\Common\Lexer\Abstract_Lexer``
and implement the following three abstract methods.

.. code-block:: php

    /**
     * Lexical catchable patterns.
     *
     * @return string[]
     */
    abstract protected function get_catchable_patterns();

    /**
     * Lexical non-catchable patterns.
     *
     * @return string[]
     */
    abstract protected function get_non_catchable_patterns();

    /** Retrieve token type. Also processes the token value if necessary. */
    abstract protected function get_type(string &$value): int;

These methods define the `lexical <http://en.wikipedia.org/wiki/Lexical_analysis>`_
catchable and non-catchable patterns and a method for returning the
type of a token and filtering the value if necessary.

The Lexer is responsible for giving you an API to walk across a
string one character at a time and analyze the type of each character, value and position of
each token in the string. The low level API of the lexer is pretty simple:

- ``set_input($input)`` - Sets the input data to be tokenized. The Lexer is immediately reset and the new input tokenized.
- ``reset()`` - Resets the lexer.
- ``reset_peek()`` - Resets the peek pointer to 0.
- ``reset_position($position = 0)`` - Resets the lexer position on the input to the given position.
- ``is_next_token($token)`` - Checks whether a given token matches the current lookahead.
- ``is_next_token_any(array $tokens)`` - Checks whether any of the given tokens matches the current lookahead.
- ``move_next()`` - Moves to the next token in the input string.
- ``skip_until($type)`` - Tells the lexer to skip input tokens until it sees a token with the given value.
- ``is_a($value, $token)`` - Checks if given value is identical to the given token.
- ``peek()`` - Moves the lookahead token forward.
- ``glimpse()`` - Peeks at the next token, returns it and immediately resets the peek.
