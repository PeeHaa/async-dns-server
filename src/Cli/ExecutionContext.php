<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Cli;

final class ExecutionContext
{
    private string $binary;

    private string $command;

    /** @var array<ExecutionFlag> */
    private array $flags = [];

    public function __construct(string $binary, string $command)
    {
        $this->binary  = $binary;
        $this->command = $command;
    }

    public function addFlag(ExecutionFlag $flag): void
    {
        $this->flags[] = $flag;
    }

    public function getBinary(): string
    {
        return $this->binary;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function hasFlag(string $name): bool
    {
        /** @var ExecutionFlag $flag */
        foreach ($this->flags as $flag) {
            if ($flag->getFlag() !== $name) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function getFlag(string $name): ?ExecutionFlag
    {
        /** @var ExecutionFlag $flag */
        foreach ($this->flags as $flag) {
            if ($flag->getFlag() === $name) {
                return $flag;
            }
        }

        return null;
    }
}
