<?php

namespace App\Command;

use App\Vim;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class QuitCommand extends AbstractVimCommand
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->getApplication()->quit();

        return Command::SUCCESS;
    }
}
