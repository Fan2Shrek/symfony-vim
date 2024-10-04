<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WriteCommand extends Command implements VimCommandInterface
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // @todo
        // dd("write");

        return Command::SUCCESS;
    }

    public function getResult(): string
    {
        return \sprintf("Text has been written to the file");
    }
}
