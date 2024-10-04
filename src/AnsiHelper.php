<?php

namespace App;

abstract class AnsiHelper
{
    public const RED_BACKGROUND = 41;

    public const WHITE_TEXT = 37;

    public static function cursorTo(int $x, int $y): string
    {
        return "\x1b[{$y};{$x}H";
    }

    public static function hideCursor(): string
    {
        return "\x1b[?25l";
    }

    public static function showCursor(): string
    {
        return "\x1b[?25h";
    }

    public static function clearScreen(): string
    {
        return "\x1b[2J";
    }

    public static function errorMessage(string $message): string
    {
        return \sprintf("\x1b[%um\x1b[%um%s\x1b[0m", self::RED_BACKGROUND, self::WHITE_TEXT, $message);
    }
}
