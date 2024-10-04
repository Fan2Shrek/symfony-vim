<?php

namespace App\Command;

use App\Vim;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

abstract class AbstractVimCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('command', InputArgument::OPTIONAL);
    }

    public function getApplication(): ?Vim
    {
        $app = parent::getApplication();

        if (!$app instanceof Vim) {
            throw new \RuntimeException('The application is not a Vim instance.');
        }

        return $app;
    }
}
