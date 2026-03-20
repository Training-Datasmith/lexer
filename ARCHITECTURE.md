# Architecture: lexer

## Purpose
Doctrine Common Lexer — a base abstract class for building simple tokenizing lexers. Provides the scanning, position, peeking, and token navigation infrastructure, leaving only the regex patterns and token classification to subclasses.

## Directory Structure
```
src/
  Abstract_Lexer.php   # Generic abstract lexer — setInput, moveNext, peek, glimpse, isNextToken, etc.
  Token.php            # Token value object — type (T), value (V), and position
tests/
```

## Key Design Decisions
- **Template method pattern** — `Abstract_Lexer` handles all cursor/position management. Subclasses must implement only `getCatchablePatterns()`, `getNonCatchablePatterns()`, and `getType()` to define what tokens to match.
- **Enum-typed tokens** — the generic parameter `T extends UnitEnum|string|int` allows subclasses to type their token kinds as PHP 8.1 `UnitEnum` cases, improving IDE support and exhaustive switch handling.
- **Lookahead support** — `peek()` advances a secondary position without affecting the main cursor, enabling one-token or multi-token lookahead without backtracking.
- **Offset capture** — tokens include their byte offset in the original input string, enabling accurate error reporting with line/column information in the calling parser.

## Extension Points
- Extend `Abstract_Lexer` and implement `getCatchablePatterns()`, `getNonCatchablePatterns()`, and `getType()`.
- Use the concrete lexer in a recursive-descent or Pratt parser to tokenize a DSL.

## Dependency Flow
```
Concrete_Lexer::setInput($sql)
  └─ Abstract_Lexer::scan($input)
       └─ preg_split(catchablePatterns + nonCatchablePatterns) → Token[]

Concrete_Lexer::moveNext() → bool
Concrete_Lexer::peek() → Token|null
Concrete_Lexer::isNextToken(TokenType::NAME) → bool
```
