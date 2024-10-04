<?php

namespace App;

abstract class AnsiHelper
{
    public static function cursorTo(int $x, int $y): string
    {
        return "\x1b[{$y};{$x}H";
    }

    public static function hideCursor(): string
    {
        return "\x1b[?25l";
    }
}
