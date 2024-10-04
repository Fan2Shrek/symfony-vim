<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Vim;

class WriteCommand extends AbstractVimCommand implements VimCommandInterface
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('command');

        if (!\is_file($file)) {
            touch($file);
        }

        $app = $this->getApplication();

        @\file_put_contents($file, $this->getApplication()->getContent());

        return Command::SUCCESS;
    }

    public function getResult(): string
    {
        return \sprintf("Text has been written to the file");
    }
}
