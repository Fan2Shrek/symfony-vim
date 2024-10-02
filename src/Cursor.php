<?php

namespace App;

final class Cursor
{
    public int $x;
    public int $y;

    public function __construct(int $x, int $y)
    {
       $this->x = $x;
       $this->y = $y;
    }
}
