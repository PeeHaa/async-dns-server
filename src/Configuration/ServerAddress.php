<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Configuration;

final class ServerAddress
{
    public const TYPE_UDP = 'udp';
    public const TYPE_TCP = 'tcp';

    private string $type;

    private string $ipAddress;

    private int $port;

    public function __construct(string $type, string $ipAddress, int $port = 53)
    {
        $this->type      = $type;
        $this->ipAddress = $ipAddress;
        $this->port      = $port;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getPort(): int
    {
        return $this->port;
    }
}
