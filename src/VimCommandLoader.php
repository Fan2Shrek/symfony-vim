<?php

namespace App;

use App\Command as VimCommand;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class VimCommandLoader implements CommandLoaderInterface
{
    private array $commands = [
        'w' => VimCommand\WriteCommand::class,
        'write' => VimCommand\WriteCommand::class,
    ];

    public function get(string $name): Command
    {
        if (!isset($this->commands[$name])) {
            throw new CommandNotFoundException(\sprintf('Command "%s" does not exist.', $name));
        }

        return new $this->commands[$name]($name);
    }

    public function has(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    public function getNames(): array
    {
        return \array_keys($this->commands);
    }
}
