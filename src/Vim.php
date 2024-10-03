<?php

namespace App;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

class Vim extends Application
{
    private const NAME = 'Symfony/Vim';
    private const VERSION = '0-DEV';

    /** @var ressource $input */
    private $input;
    private bool $run;
    private Buffer $buffer;
    private OutputInterface $output;
    private Terminal $terminal;
    private Cursor $cursor;

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);
    }

    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $this->init();
        $this->output = $output;

        $this->prepareBuffer();
        $this->renderFile(null);

        $this->run = true;

        while ($this->run) {
            $this->handleInput();
            $this->renderBuffer();
        }

        $this->onTerminate();

        return Command::SUCCESS;
    }

    private function handleInput(): void
    {
        while ($c = @fread($this->input, 1)) {
            switch ($c) {
                case 'h':
                case 'j':
                case 'k':
                case 'l':
                    $this->move($c);
                    break;
            };
        }
    }

    private function move(string $direction): void
    {
        switch ($direction) {
            case 'h':
                $this->cursor->x--;
                break;
            case 'j':
                $this->cursor->y--;
                break;
            case 'k':
                $this->cursor->y++;
                break;
            case 'l':
                $this->cursor->x++;
                break;
        }
    }

    private function prepareBuffer(): void
    {
        // $this->buffer->addContent("\x1b[?25l"); // Hide cursor
        $this->buffer->addContent("\x1b[2J"); // Clear screen
    }

    private function renderBuffer(): void
    {
        $this->buffer->addContent(\sprintf("\x1b[%d;%dH", $this->cursor->y, $this->cursor->x));
        $this->output->write($this->buffer->flush());
    }

    private function renderFile(?string $file): void
    {
        if (null !== $file) {
            // @todo
            throw new \RuntimeException('Not implemented yet');
        }

        for ($i = 0; $i < $this->getHeight(); $i++) {
            if ($this->getHeight() / 2 === $i) {
                $text = \sprintf("%s -- %s", $this->getName(), $this->getVersion());
                $padding = \str_repeat(' ', ($this->getWidth() - 2 - \strlen($text)) / 2);
                $fullLine = \sprintf("~%s%s%s\n", $padding, $text, $padding);
            } else {
                $fullLine = \sprintf("~%s\n", \str_repeat(' ', $this->getWidth() - 3));
            }

            $this->buffer->addContent($fullLine);
        }
    }

    private function getHeight(): int
    {
        return $this->terminal->getHeight();
    }

    private function getWidth(): int
    {
        return $this->terminal->getWidth();
    }

    public function onTerminate(): void
    {
        exec('stty sane');

        $this->buffer->clear();
        $this->buffer->addContent("\x1b[2J"); // Clear screen
        $this->buffer->addContent("\x1b[H"); // Move to top
        $this->buffer->addContent("\x1b[?25h"); // Show cursor
        $this->renderBuffer();
    }

    public function init()
    {

        $this->terminal = new Terminal();
        $this->buffer = new Buffer('');
        $this->cursor = new Cursor(0, 0);

        $this->input = \defined('STDIN') ? \STDIN : @fopen('php://input', 'r+');
        stream_set_blocking($this->input, false);
        exec('stty -icanon');

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function () {
                $this->run = false;
            });
            // \pcntl_signal(SIGINT, fn() => '');
        }
    }
}
