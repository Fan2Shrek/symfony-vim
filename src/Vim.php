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
    /** @var string[] */
    private array $inputs = [];
    private bool $run;
    private Buffer $buffer;
    private Buffer $currentBuffer;
    private OutputInterface $output;
    private Terminal $terminal;
    private Cursor $cursor;
    private string $content = '';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);
    }

    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $this->init();
        $this->output = $output;

        $this->prepareBuffer();
        $this->renderDefaultScreen(null);

        $this->run = true;
        $this->renderBuffer();
        $this->buffer->addContent("\x1b[1;1H");

        while ($this->run) {
            $this->handleInput();
            if ($this->hasUnhandledInputs()) {
                $this->renderBuffer();
            }
        }

        $this->onTerminate();

        return Command::SUCCESS;
    }

    private function handleInput(): void
    {
        while ($c = @fread($this->input, 1)) {
            $this->inputs[] = $c;
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
                if (0 < $this->cursor->x) {
                    $this->cursor->x--;
                }
                break;
            case 'j':
                if ($this->getHeight() - 1 > $this->cursor->y) {
                    $this->cursor->y++;
                }
                break;
            case 'k':
                if (0 < $this->cursor->y) {
                    $this->cursor->y--;
                }
                break;
            case 'l':
                if ($this->getWidth() - 1 > $this->cursor->x) {
                    $this->cursor->x++;
                }
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
        $this->buffer->addContent("\x1bc");
        $this->processContent();
        $this->processStatusBar();

        if ($this->currentBuffer->getContent() === $this->buffer->getContent()) {
            $this->buffer->clear();
            return;
        }

        $this->buffer->addContent(\sprintf("\x1b[%u;%uH", $this->cursor->y, $this->cursor->x));
        $this->currentBuffer = clone $this->buffer;
        $this->output->write($this->buffer->flush());

        $this->inputs = [];
    }

    private function renderDefaultScreen(?string $file): void
    {
        if (null !== $file) {
            // @todo
            throw new \RuntimeException('Not implemented yet');
        }

        for ($i = 0; $i < $this->getHeight() - 1; $i++) {
            if (round($this->getHeight() / 3) == $i) {
                $text = \sprintf("%s -- %s", $this->getName(), $this->getVersion());
                $padding = \str_repeat(' ', ($this->getWidth() - 2 - \strlen($text)) / 2);
                $fullLine = \sprintf("~%s%s%s\n", $padding, $text, $padding);
            } else {
                $fullLine = \sprintf("~\n");
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

    private function processContent(): void
    {
        if ('' !== $this->content) {
            return;
        }

        $this->renderDefaultScreen(null);
    }

    private function processStatusBar(): void
    {
        static $padding;

        $text = \sprintf("%u, %u", $this->cursor->y, $this->cursor->x);
        $padding ??= \str_repeat('', ($this->getWidth() - 1 - \strlen($text)) / 2);

        $this->buffer->addContent(\sprintf("%s%s%s\n", $padding, $text, $padding));
    }

    private function hasUnhandledInputs(): bool
    {
        return !empty($this->inputs);
    }

    public function onTerminate(): void
    {
        exec('stty sane');

        $this->buffer->clear();
        $this->buffer->addContent("\x1b[2J"); // Clear screen
        $this->buffer->addContent("\x1b[0;0H"); // Move to top
        $this->buffer->addContent("\x1b[?25h"); // Show cursor
        $this->renderBuffer();
    }

    public function init()
    {
        $this->terminal = new Terminal();
        $this->buffer = new Buffer('');
        $this->currentBuffer = new Buffer('');
        $this->cursor = new Cursor(1, 1);

        $this->input = \defined('STDIN') ? \STDIN : @fopen('php://input', 'r+');
        stream_set_blocking($this->input, false);
        // exec('stty -icanon');
        exec('stty cbreak -echo');

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function () {
                $this->run = false;
            });
            // \pcntl_signal(SIGINT, fn() => '');
        }
    }
}
