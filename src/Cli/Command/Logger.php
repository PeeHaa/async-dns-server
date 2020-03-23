<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Cli\Command;

use League\CLImate\CLImate;

final class Logger
{
    private CLImate $climate;

    private string $logger;

    public function __construct(CLImate $climate)
    {
        $this->climate = $climate;
    }

    public function run(): void
    {
        $this->climate->br();

        $this->askForLogging();
    }

    private function askForLogging(): void
    {
        $enableLogging = $this->climate
            ->input('Do you want to enable logging? [n]')
            ->accept(['n', 'no', 'y', 'yes'])
            ->defaultTo('n')
            ->prompt()
        ;

        if (in_array($enableLogging, ['n', 'no'], true)) {
            $this->logger = 'NullLogger';

            return;
        }

        $this->logger = 'StdOutLogger';
    }

    public function getCurrentSettings(): string
    {
        return $this->logger;
    }
}
