<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Vim;
use Symfony\Component\Console\Input\InputArgument;

class WriteCommand extends AbstractVimCommand implements VimCommandInterface
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->hasArgument('file')) {
            $file = $input->getArgument('file');
        }

        if (!\is_file($file)) {
            touch($file);
        }

        @\file_put_contents($file, $this->getApplication()->getContent() . "\n");

        return Command::SUCCESS;
    }

    public function getResult(): string
    {
        return \sprintf("Text has been written to the file");
    }
}
