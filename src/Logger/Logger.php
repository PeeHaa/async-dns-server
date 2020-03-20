<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Logger;

use Amp\Socket\SocketAddress;
use Monolog\Logger as MonologLogger;
use PeeHaa\AsyncDnsServer\Configuration\ServerAddress;
use PeeHaa\AsyncDnsServer\Message;

final class Logger
{
    private MonologLogger $logger;

    public function __construct(MonologLogger $logger)
    {
        $this->logger = $logger;
    }

    public function started(ServerAddress $serverAddress): void
    {
        $this->logger->info(
            sprintf(
                'DNS server started at %s://%s:%d',
                $serverAddress->getType(),
                $serverAddress->getIpAddress(),
                $serverAddress->getPort(),
            ),
        );
    }

    public function incomingPacket(string $packet, SocketAddress $client): void
    {
        $this->logger->debug(sprintf('Received package from %s: %s', $client->toString(), bin2hex($packet)));
    }

    public function query(Message $message): void
    {
        $this->logger->debug('Received query', ['message' => $message->toArray()]);
    }

    public function openConnectionToExternalResolver(string $ipAddress, int $port): void
    {
        $this->logger->info(sprintf('Attempting to connect to resolver at %s:%d', $ipAddress, $port));
    }

    public function connectedToExternalResolver(string $ipAddress, int $port): void
    {
        $this->logger->info(sprintf('Connected to resolver at %s:%d', $ipAddress, $port));
    }

    public function sendQueryToExternalResolver(Message $message, string $ipAddress, int $port): void
    {
        $this->logger->debug(
            sprintf('Sending query to external resolver at %s:%d', $ipAddress, $port),
            ['message' => $message->toArray()],
        );
    }

    public function sendPacketToExternalResolver(string $packet, string $ipAddress, int $port): void
    {
        $this->logger->debug(
            sprintf('Sending packet to external resolver at %s:%d: %s', $ipAddress, $port, bin2hex($packet)),
        );
    }

    public function receivedResponseFromExternalResolver(string $packet, string $ipAddress, int $port): void
    {
        $this->logger->debug(
            sprintf('Received packet from external resolver at %s:%d: %s', $ipAddress, $port, bin2hex($packet)),
        );
    }

    public function receivedMessageFromExternalResolver(Message $message, string $ipAddress, int $port): void
    {
        $this->logger->debug(
            sprintf('Received message from external resolver at %s:%d', $ipAddress, $port),
            ['message' => $message->toArray()],
        );
    }

    public function answerQuery(Message $message, SocketAddress $client): void
    {
        $this->logger->debug(
            sprintf('Sending answer to %s', $client->toString()),
            ['message' => $message->toArray()],
        );
    }

    public function networkError(\Throwable $e): void
    {
        $this->logger->error($e->getMessage(), ['exception' => $e]);
    }
}
