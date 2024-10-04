<?php

namespace App;

use App\Command\VimCommandInterface;
use App\Enum\ModeEnum;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;
use App\Command\MultipleCommand;

class Vim extends Application
{
    private const NAME = 'Symfony/Vim';
    private const VERSION = '0-DEV';

    /** @var ressource $inputRessource */
    private $inputRessource;
    /** @var array<int, string> $changedLines */
    private array $changedLines = [];
    /** @var array<int, string> $content */
    private array $content = [];
    private bool $run;
    private Buffer $buffer;
    private InputInterface $input;
    private OutputInterface $output;
    private Terminal $terminal;
    private Cursor $cursor;
    private ModeEnum $mode = ModeEnum::NORMAL;
    private string $statusBar = '';
    private bool $displayWelcome = true;
    private int $height = 1;

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);
    }

    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $this->init();
        $this->input = $input;
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
        while ($c = @fread($this->inputRessource, 1)) {
            $c = $this->normalize($c);

            if (($this->isCommandMode() || $this->isInsertMode()) && "\x1b" !== $c) {
                $this->insert($c);

                continue;
            }

            switch ($c) {
                case 'h':
                case 'j':
                case 'k':
                case 'l':
                    $this->move($c);
                    break;
                case 'i':
                    $this->changeMode(ModeEnum::INSERT);
                    break;
                case "\x1b":
                    $this->changeMode(ModeEnum::NORMAL);
                    break;
                case 'v':
                    $this->changeMode(ModeEnum::VISUAL);
                    break;
                case ":":
                    $this->enterCommandMode();
                    break;
            };
        }
    }

    private function normalize(string $c): string
    {
        return match ($c) {
            default => $c,
        };
    }

    private function enterCommandMode(): void
    {
        $this->output->write(AnsiHelper::hideCursor());
        $this->cursor->y = $this->getHeight() - 1;
        $this->cursor->x = 3;
        $this->statusBar = ':';

        $this->changeMode(ModeEnum::COMMAND);
    }

    private function changeMode(ModeEnum $mode): void
    {
        $this->mode = $mode;
        $this->processStatusBar();
    }

    private function isInsertMode(): bool
    {
        return ModeEnum::INSERT === $this->mode;
    }

    private function isCommandMode(): bool
    {
        return ModeEnum::COMMAND === $this->mode;
    }

    private function insert(string $c): void
    {
        if ($this->isCommandMode()) {
            if ("\n" === $c) {
                $this->execute(\str_replace(':', '', $this->statusBar));
            } elseif ("\x7f" === $c) {
                $this->changedLines[$this->cursor->y] = $this->statusBar = \substr($this->statusBar, 0, -1);
            } else {
                $this->changedLines[$this->cursor->y] = $this->statusBar .= $c;
            }

            return;
        }

        if ($this->displayWelcome) {
            $this->changedLines[round($this->getHeight() / 3) + 1] = '~';
            $this->displayWelcome = false;
        }

        if ("\n" === $c) {
            $content = \substr_replace($this->content[$this->cursor->y] ?? '', "\n", $this->cursor->x, 0);
            [$before, $after] = \explode("\n", $content);
            $this->changedLines[$this->cursor->y] = $this->content[$this->cursor->y] = $before;
            $this->changedLines[$this->cursor->y + 1] = $this->content[$this->cursor->y + 1] = $after;

            $this->cursor->x = 1;
            $this->height++;
            $this->cursor->y++;

            return;
        }

        if ("\x7f" === $c) {
            $this->changedLines[$this->cursor->y] = $this->content[$this->cursor->y] = \substr($this->content[$this->cursor->y] ?? '', 0, -1);
            $this->cursor->x--;

            return;
        }

        $this->changedLines[$this->cursor->y] = $this->content[$this->cursor->y] = \substr_replace($this->content[$this->cursor->y] ?? '', $c, $this->cursor->x, 0);
        $this->cursor->x++;
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
                if ($this->height > $this->cursor->y) {
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
        $this->processStatusBar();
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

        for ($i = 1; $i < $this->getHeight() - 1; $i++) {
            if ($this->displayWelcome && round($this->getHeight() / 3) == $i) {
                $text = \sprintf("%s -- %s", $this->getName(), $this->getVersion());
                $padding = \str_repeat(' ', ($this->getWidth() - 2 - \strlen($text)) / 2);
                $fullLine = \sprintf("~%s%s%s\n", $padding, $text, $padding);
            } else {
                $fullLine = \sprintf("~\n");
            }

            $this->buffer->addContent($fullLine);
        }

        $this->content[1] = '';
    }

    private function execute(string $command): void
    {
        $command = $this->getCommand($command);

        $this->cursor->y = 1;
        $this->output->write(AnsiHelper::showCursor());
        $this->output->write(AnsiHelper::cursorTo(1, 1));

        try {
            $command->run($this->input, $this->output);
        } catch (\Exception $e) {
            $this->statusBar = AnsiHelper::errorMessage($e->getMessage());
            $this->changedLines[$this->getHeight() - 1] = $this->statusBar;

            return;
        }

        if ($command instanceof VimCommandInterface) {
            $this->changedLines[$this->getHeight() - 1] = $command->getResult();
            $this->statusBar = '';
        }
    }

    public function getCommand(string $command): Command
    {
        return $this->get($command);
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

        if (ModeEnum::COMMAND === $this->mode) {
            $this->changedLines[$this->getHeight() - 1] = $this->statusBar;

            return;
        }

        $modeText = \sprintf(" -- %s -- ", $this->mode->value);
        $cursorText = \sprintf("%u, %u", $this->cursor->y, $this->cursor->x);
        $length = \strlen($modeText . $cursorText);
        $padding ??= ($this->getWidth() - 5 - $length);

        $sprintf = "%s%-" . $padding . "s%s\n";
        $this->changedLines[$this->getHeight() - 1] = \sprintf($sprintf, $modeText, ' ', $cursorText);
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

    public function has(string $name): bool
    {
        if (parent::has($name)) {
            return true;
        }

        $commands = \str_split($name);

        foreach ($commands as $command) {
            if (!parent::has($command)) {
                return false;
            }
        }

        return true;
    }

    public function get(string $name): Command
    {
        if (parent::has($name)) {
            return parent::get($name);
        }

        if (!$this->has($name)) {
            $this->statusBar = \sprintf("%s is not a vim command", $name);
        }

        $commandNames = \str_split($name);
        $commands = [];

        foreach ($commandNames as $command) {
            $commands[] = parent::get($command);
        }

        return new MultipleCommand(...$commands);
    }

    public function onTerminate(): void
    {
        exec('stty sane');

        $this->output->write(AnsiHelper::clearScreen()); // Clear screen
        $this->output->write(AnsiHelper::cursorTo(1, 1)); // Move to top
        $this->output->write("\x1bc"); // Move to top
    }

    public function init(): void
    {
        $this->setCommandLoader(new VimCommandLoader());
        $this->terminal = new Terminal();
        $this->buffer = new Buffer('');
        $this->cursor = new Cursor(1, 1);

        $this->inputRessource = \defined('STDIN') ? \STDIN : @fopen('php://input', 'r+');
        stream_set_blocking($this->inputRessource, false);
        exec('stty cbreak -echo');

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, $this->quit(...));
        }
    }

    public function getContent(): string
    {
        return \implode("\n", $this->content);
    }

    public function quit(): void
    {
        $this->run = false;
    }
}
