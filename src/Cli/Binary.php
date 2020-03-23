<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Cli;

use League\CLImate\CLImate;
use PeeHaa\AsyncDnsServer\Exception\MissingCliCommand;

final class Binary
{
    private CLImate $climate;

    private string $title;

    /** @var array<Usage> */
    private array $usages = [];

    /** @var array<Command> */
    private array $commands = [];

    /** @var array<Flag> */
    private array $flags = [];

    public function __construct(CLImate $climate, string $title)
    {
        $this->climate = $climate;
        $this->title   = $title;
    }

    public function process(array $arguments): ExecutionContext
    {
        $binary  = pathinfo(array_shift($arguments))['filename'];
        $command = array_shift($arguments);

        if ($command === null || in_array($command, ['-h', '--help'])) {
            $this->renderHelp();
        }

        if (!in_array($command, ['setup', 'start'], true)) {
            $this->renderError('Missing command (setup | start) in binary invocation');
        }

        $executionContext = new ExecutionContext($binary, $command);

        foreach ($arguments as $argument) {
            $flagPattern = '~^(?:(?P<shortOrLong>--?)(?P<flag>[a-z]+))(?:(?P<hasData>=)(?P<data>.+))?$~i';

            if (preg_match($flagPattern, $argument, $argumentInfo, PREG_UNMATCHED_AS_NULL) !== 1) {
                $this->renderError('Invalid syntax when parsing flags');
            }

            $flag = $this->getFlag($argumentInfo['flag']);

            if ($flag === null) {
                $this->renderError(sprintf('Unrecognized flag %s', $argumentInfo['flag']));
            }

            $executionContext->addFlag(
                new ExecutionFlag($flag->getLong(), $argumentInfo['hasData'] ? $argumentInfo['data'] : null),
            );
        }

        foreach ($this->flags as $flag) {
            if (!$flag->hasDefault()) {
                continue;
            }

            if ($executionContext->hasFlag($flag->getLong())) {
                continue;
            }

            $executionContext->addFlag(
                new ExecutionFlag($flag->getLong(), $flag->getDefault()),
            );
        }

        if ($executionContext->hasFlag('help')) {
            $this->renderHelp();
        }

        return $executionContext;
    }

    private function getFlag(string $suppliedFlag): ?Flag
    {
        /** @var Flag $flag */
        foreach ($this->flags as $flag) {
            if ($flag->getShort() === $suppliedFlag) {
                return $flag;
            }

            if ($flag->getLong() === $suppliedFlag) {
                return $flag;
            }
        }

        return null;
    }

    public function addUsage(Usage $usage): self
    {
        $this->usages[] = $usage;

        return $this;
    }

    public function addCommand(Command $command): self
    {
        $this->commands[] = $command;

        return $this;
    }

    public function addFlag(Flag $flag): self
    {
        $this->flags[] = $flag;

        return $this;
    }

    public function renderError(string $message): void
    {
        $this->climate->br();
        $this->climate->error($message);
        $this->climate->br();
        $this->climate->info('Run with --help for usage help');

        exit(1);
    }

    public function renderHelp(): void
    {
        $this->climate->br();
        $this->climate->info($this->title);
        $this->climate->br();
        $this->climate->info('Usage:');
        $this->climate->br();

        array_map(fn(Usage $usage) => $usage->render(), $this->usages);

        $this->climate->br();
        $this->climate->info('Commands:');
        $this->climate->br();

        array_map(fn(Command $command) => $command->render(), $this->commands);

        $this->climate->br();
        $this->climate->info('Flags:');
        $this->climate->br();

        array_map(fn(Flag $flag) => $flag->render(), $this->flags);

        exit(0);
    }
}