<?php

namespace App\Command;

interface VimCommandInterface
{
    public function getResult(): string;
}
