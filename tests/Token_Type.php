<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

enum Token_Type
{
    case INT;
    case OPERATOR;
    case STRING;
}
