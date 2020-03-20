<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Configuration;

use PeeHaa\AsyncDnsServer\Logger\Logger;
use PeeHaa\AsyncDnsServer\Resolver\Resolver;

final class Configuration
{
    private Logger $logger;

    private Resolver $resolver;

    /** @var array<ServerAddress> */
    private array $serverAddresses = [];

    public function __construct(Logger $logger, Resolver $resolver, ServerAddress $serverAddress, ServerAddress ...$serverAddresses)
    {
        $this->logger          = $logger;
        $this->resolver        = $resolver;
        $this->serverAddresses = array_merge([$serverAddress], $serverAddresses);
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function getResolver(): Resolver
    {
        return $this->resolver;
    }

    /**
     * @return array<ServerAddress>
     */
    public function getServerAddresses(): array
    {
        return $this->serverAddresses;
    }
}
