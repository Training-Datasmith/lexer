# Architecture: lexer

## Purpose
Doctrine Common Lexer — a base abstract class for building simple tokenizing lexers. Provides the scanning, position, peeking, and token navigation infrastructure, leaving only the regex patterns and token classification to subclasses.

## Directory Structure
```
src/
  Abstract_Lexer.php   # Generic abstract lexer — set_input, move_next, peek, glimpse, is_next_token, etc.
  Token.php            # Token value object — type (T), value (V), and position
tests/
```

## Key Design Decisions
- **Template method pattern** — `Abstract_Lexer` handles all cursor/position management. Subclasses must implement only `get_catchable_patterns()`, `get_non_catchable_patterns()`, and `get_type()` to define what tokens to match.
- **Enum-typed tokens** — the generic parameter `T extends UnitEnum|string|int` allows subclasses to type their token kinds as PHP 8.1 `UnitEnum` cases, improving IDE support and exhaustive switch handling.
- **Lookahead support** — `peek()` advances a secondary position without affecting the main cursor, enabling one-token or multi-token lookahead without backtracking.
- **Offset capture** — tokens include their byte offset in the original input string, enabling accurate error reporting with line/column information in the calling parser.

## Extension Points
- Extend `Abstract_Lexer` and implement `get_catchable_patterns()`, `get_non_catchable_patterns()`, and `get_type()`.
- Use the concrete lexer in a recursive-descent or Pratt parser to tokenize a DSL.

## Dependency Flow
```
Concrete_Lexer::set_input($sql)
  └─ Abstract_Lexer::scan($input)
       └─ preg_split(catchablePatterns + nonCatchablePatterns) → Token[]

Concrete_Lexer::move_next() → bool
Concrete_Lexer::peek() → Token|null
Concrete_Lexer::is_next_token(Token_Type::NAME) → bool
```
