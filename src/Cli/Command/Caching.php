<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Cli\Command;

use League\CLImate\CLImate;

final class Caching
{
    private CLImate $climate;

    private array $cache = [];

    public function __construct(CLImate $climate)
    {
        $this->climate = $climate;
    }

    public function run(): void
    {
        $this->climate->br();

        $enableCaching = $this->askForCaching();

        if (in_array($enableCaching, ['n', 'no'], true)) {
            $this->cache = [];

            return;
        }

        $this->askForCachingType();
    }

    private function askForCaching(): string
    {
        return $this->climate
            ->input('Do you want to enable caching? [y]')
            ->accept(['y', 'yes', 'n', 'no'])
            ->defaultTo('y')
            ->prompt()
        ;
    }

    private function askForCachingType(): void
    {
        $storage = $this->climate
            ->input('What cache do you want to use (memory, redis)? [redis]')
            ->defaultTo('redis')
            ->accept(['memory', 'redis'])
            ->prompt()
        ;

        if ($storage === 'memory') {
            $this->cache = [
                'type' => 'memory',
            ];

            return;
        }

        $this->askForRedisConfiguration();
    }

    private function askForRedisConfiguration(): void
    {
        $address = $this->climate
            ->input('What is the address of the Redis instance? [127.0.0.1]')
            ->defaultTo('127.0.0.1')
            ->prompt()
        ;

        $port = $this->climate
            ->input('What is the port of the Redis instance? [6379]')
            ->accept(fn ($response) => ctype_digit($response))
            ->defaultTo('6379')
            ->prompt()
        ;

        $this->cache = [
            'type'    => 'redis',
            'address' => [$address, $port],
        ];
    }

    public function getCurrentSettings(): array
    {
        return $this->cache;
    }
}
