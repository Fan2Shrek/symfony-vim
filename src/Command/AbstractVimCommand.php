<?php

namespace App\Command;

use App\Vim;
use Symfony\Component\Console\Command\Command;

abstract class AbstractVimCommand extends Command
{


    public function getApplication(): ?Vim
    {
        $app = parent::getApplication();

        if (!$app instanceof Vim) {
            throw new \RuntimeException('The application is not a Vim instance.');
        }

        return $app;
    }
}
