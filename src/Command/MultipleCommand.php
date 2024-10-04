<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class MultipleCommand extends AbstractVimCommand
{
    private array $commands;

    public function __construct(Command ...$commands)
    {
        parent::__construct();

        $this->commands = $commands;
    }

    protected function configure(): void
    {
        $this->addArgument('command', InputArgument::OPTIONAL);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->commands as $command) {
            $command->execute($input, $output);
        }

        return Command::SUCCESS;
    }
}
