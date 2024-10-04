<?php

namespace App\Enum;

enum ModeEnum: string
{
    case COMMAND = 'COMMAND';
    case NORMAL = 'NORMAL';
    case INSERT = 'INSERT';
    case VISUAL = 'VISUAL';
}
