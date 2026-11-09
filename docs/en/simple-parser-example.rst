Simple Parser Example
=====================

Extend the ``Doctrine\Common\Lexer\Abstract_Lexer`` class and implement
the ``get_catchable_patterns``, ``get_non_catchable_patterns``, and ``get_type``
methods. Here is a very simple example lexer implementation named ``Character_Type_Lexer``.
It tokenizes a string to ``T_UPPER``, ``T_LOWER`` and``T_NUMBER`` tokens:

.. code-block:: php
    <?php

    use Doctrine\Common\Lexer\Abstract_Lexer;

    /**
     * @extends Abstract_Lexer<Character_Type_Lexer::T_*, string>
     */
    class Character_Type_Lexer extends Abstract_Lexer
    {
        const T_UPPER =  1;
        const T_LOWER =  2;
        const T_NUMBER = 3;

        protected function get_catchable_patterns(): array
        {
            return [
                '[a-zA-Z0-9]',
            ];
        }

        protected function get_non_catchable_patterns(): array
        {
            return [];
        }

        protected function get_type(&$value): int
        {
            if (is_numeric($value)) {
                return self::T_NUMBER;
            }

            if (strtoupper($value) === $value) {
                return self::T_UPPER;
            }

            if (strtolower($value) === $value) {
                return self::T_LOWER;
            }
        }
    }

Use ``Character_Type_Lexer`` to extract an array of upper case characters:

.. code-block:: php
    <?php

    class Upper_Case_Character_Extracter
    {
        public function __construct(private Character_Type_Lexer $lexer)
        {
        }

        /** @return list<string> */
        public function get_upper_case_characters(string $string): array
        {
            $this->lexer->set_input($string);
            $this->lexer->move_next();

            $upper_case_chars = [];
            while (true) {
                if (!$this->lexer->lookahead) {
                    break;
                }

                $this->lexer->move_next();

                if ($this->lexer->token->is_a(Character_Type_Lexer::T_UPPER)) {
                    $upper_case_chars[] = $this->lexer->token->value;
                }
            }

            return $upper_case_chars;
        }
    }

    $upper_case_character_extractor = new Upper_Case_Character_Extracter(new Character_Type_Lexer());
    $upper_case_characters = $upper_case_character_extractor->get_upper_case_characters('1aBcdEfgHiJ12');

    print_r($upper_case_characters);

The variable ``$upper_case_characters`` contains all of the upper case
characters:

.. code-block:: php
    Array
    (
        [0] => B
        [1] => E
        [2] => H
        [3] => J
    )

This is a simple example but it should demonstrate the low level API
that can be used to build more complex parsers.
