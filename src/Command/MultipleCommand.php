<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MultipleCommand extends AbstractVimCommand
{
    private array $commands;

    public function __construct(Command ...$commands)
    {
        parent::__construct();

        $this->commands = $commands;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->commands as $command) {
            $command->execute($input, $output);
        }

        return Command::SUCCESS;
    }
}
