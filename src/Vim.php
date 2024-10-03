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
    /** @var int[] $changedLines */
    private array $changedLines = [];
    private bool $run;
    private Buffer $buffer;
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
            $this->renderChangedLines();
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
                if (1 < $this->cursor->x) {
                    $this->cursor->x--;
                }
                break;
            case 'j':
                if ($this->getHeight() - 2 > $this->cursor->y) { // 2 for status bar
                    $this->cursor->y++;
                }
                break;
            case 'k':
                if (1 < $this->cursor->y) {
                    $this->cursor->y--;
                }
                break;
            case 'l':
                if ($this->getWidth() - 1 > $this->cursor->x) {
                    $this->cursor->x++;
                }
                break;
        }
        $this->output->write(AnsiHelper::cursorTo($this->cursor->x, $this->cursor->y));
        $this->processStatusBar(false);
    }

    private function prepareBuffer(): void
    {
        $this->buffer->addContent("\x1b[2J"); // Clear screen
    }

    private function renderBuffer(): void
    {
        $this->processStatusBar();
        $this->buffer->addContent(AnsiHelper::cursorTo($this->cursor->x, $this->cursor->y));

        $this->output->write($this->buffer->flush());
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

    private function processStatusBar(bool $withBuffer = true): void
    {
        static $padding;

        $text = \sprintf("%u, %u", $this->cursor->y, $this->cursor->x);
        $padding ??= \str_repeat(' ', ($this->getWidth() - 1 - \strlen($text)) / 2);

        if (0) {
            $this->buffer->addContent(\sprintf("%s%s%s\n", $padding, $text, $padding));
        } else {
            $this->changedLines[$this->getHeight() - 1] = \sprintf("%s%s%s\n", $padding, $text, $padding);
        }
    }

    private function renderChangedLines(): void
    {
        foreach ($this->changedLines as $lineNumber => $content) {
            $this->updateLine($lineNumber, $content);
        }

        if (!empty($this->changedLines)) {
            $this->renderBuffer();
        }

        $this->changedLines = [];
    }

    private function updateLine(int $lineNumber, string $content): void
    {
        $this->buffer->addContent(\sprintf("\x1b[%u;0H", $lineNumber));
        $this->buffer->addContent("\x1b[2K");
        $this->buffer->addContent($content);
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
        $this->cursor = new Cursor(1, 1);

        $this->input = \defined('STDIN') ? \STDIN : @fopen('php://input', 'r+');
        stream_set_blocking($this->input, false);
        exec('stty cbreak -echo');

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function () {
                $this->run = false;
            });
            // \pcntl_signal(SIGINT, fn() => '');
        }
    }
}
