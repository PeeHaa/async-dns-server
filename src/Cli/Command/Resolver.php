<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Cli\Command;

use League\CLImate\CLImate;

final class Resolver
{
    private CLImate $climate;

    private array $resolvers = [];

    public function __construct(CLImate $climate)
    {
        $this->climate = $climate;
    }

    public function run(): void
    {
        $this->climate->br();

        $resolverType = $this->askForResolverType();

        switch ($resolverType) {
            case 'external':
                $this->resolvers[] = [
                    'type'          => 'external',
                    'serverAddress' => $this->askForExternalResolverAddress(),
                ];
                break;

            case 'recursive':
                $this->resolvers[] = [
                    'type' => 'recursive',
                ];
                break;
        }

        $addResolver = $this->climate
            ->input('Do you want to stack another resolver? [n]')
            ->accept(['n', 'no', 'y', 'yes'])
            ->defaultTo('n')
            ->prompt()
        ;

        if (in_array($addResolver, ['y', 'yes'], true)) {
            $this->run();
        }
    }

    public function change(int $index): void
    {
        $this->climate->br();

        $delete = $this->climate
            ->input('Do you want to delete the resolver? [n]')
            ->accept(['no', 'n', 'yes', 'y'])
            ->prompt()
        ;

        if (in_array($delete, ['yes', 'y'], true)) {
            unset($this->resolvers[$index]);

            $this->resolvers = array_values($this->resolvers);

            if (!$this->resolvers) {
                $this->run();
            }

            return;
        }

        $resolverType = $this->askForResolverType();

        switch ($resolverType) {
            case 'external':
                $this->resolvers[$index] = [
                    'type'          => 'external',
                    'serverAddress' => $this->askForExternalResolverAddress(),
                ];
                break;

            case 'recursive':
                $this->resolvers[$index] = [
                    'type' => 'recursive',
                ];
                break;
        }
    }

    private function askForResolverType(): string
    {
        return $this->climate
            ->input('What resolver do you want to use (external, recursive)?')
            ->accept(['external', 'recursive'])
            ->prompt()
        ;
    }

    private function askForExternalResolverAddress(): array
    {
        $ipAddress = $this->climate
            ->input('What is the Ip address of the external resolver?')
            ->accept(fn ($response) => filter_var($response, FILTER_VALIDATE_IP) !== false)
            ->prompt()
        ;

        $port = $this->climate
            ->input('What is the port of the external resolver? [53]')
            ->accept(fn ($response) => ctype_digit($response))
            ->defaultTo('53')
            ->prompt()
        ;

        return [$ipAddress, (int) $port];
    }

    public function getCurrentSettings(): array
    {
        $tableData = [];

        foreach ($this->resolvers as $resolver) {
            switch ($resolver['type']) {
                case 'external':
                    $tableData[] = [
                        'resolver'      => 'External',
                        'configuration' => sprintf('%s:%d', $resolver['serverAddress'][0], $resolver['serverAddress'][1]),
                    ];
                    break;

                case 'recursive':
                    $tableData[] = [
                        'resolver'      => 'Recursive',
                        'configuration' => '',
                    ];
                    break;
            }
        }

        return $tableData;
    }
}
