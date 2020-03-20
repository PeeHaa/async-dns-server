<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer;

use Amp\Promise;
use Amp\Socket\DatagramSocket;
use Amp\Socket\ResourceSocket;
use Amp\Socket\Server as SocketServer;
use Amp\Socket\SocketAddress;
use Amp\Success;
use LibDNS\Decoder\Decoder;
use LibDNS\Encoder\Encoder;
use PeeHaa\AsyncDnsServer\Configuration\Configuration;
use PeeHaa\AsyncDnsServer\Configuration\ServerAddress;
use function Amp\asyncCall;
use function Amp\call;

final class Server
{
    private Configuration $configuration;

    private Encoder $encoder;

    private Decoder $decoder;

    public function __construct(Configuration $configuration, Encoder $encoder, Decoder $decoder)
    {
        $this->configuration = $configuration;
        $this->encoder       = $encoder;
        $this->decoder       = $decoder;
    }

    /**
     * @return Promise<null>
     */
    public function start(): Promise
    {
        /** @var ServerAddress $serverAddress */
        foreach ($this->configuration->getServerAddresses() as $serverAddress) {
            if ($serverAddress->getType() === ServerAddress::TYPE_UDP) {
                $this->startUdpServer($serverAddress);
            } elseif ($serverAddress->getType() === ServerAddress::TYPE_TCP) {
                $this->startTcpServer($serverAddress);
            }
        }

        return new Success();
    }

    private function startUdpServer(ServerAddress $serverAddress): void
    {
        asyncCall(function () use ($serverAddress) {
            $server = DatagramSocket::bind(sprintf('%s:%d', $serverAddress->getIpAddress(), $serverAddress->getPort()));

            $this->configuration->getLogger()->started($serverAddress);

            while ([$client, $data] = yield $server->receive()) {
                /** @var Message $answer */
                $answer = yield $this->processMessage($client, $data);

                yield $server->send($client, $this->encoder->encode($answer->getMessage()));
            }
        });
    }

    private function startTcpServer(ServerAddress $serverAddress): void
    {
        asyncCall(function () use ($serverAddress) {
            $server = SocketServer::listen(sprintf('%s:%d', $serverAddress->getIpAddress(), $serverAddress->getPort()));

            $this->configuration->getLogger()->started($serverAddress);

            /** @var ResourceSocket $socket */
            while ($socket = yield $server->accept()) {
                $this->processTcpClient($socket);
            }
        });
    }

    private function processTcpClient(ResourceSocket $socket): void
    {
        asyncCall(function () use ($socket) {
            while (null !== $chunk = yield $socket->read()) {
                /** @var Message $answer */
                $answer = yield $this->processMessage(
                    new SocketAddress($socket->getRemoteAddress()->getHost(), $socket->getRemoteAddress()->getPort()),
                    $chunk,
                );

                yield $socket->end($this->encoder->encode($answer->getMessage()));
            }
        });
    }

    /**
     * @return Promise<Message>
     */
    private function processMessage(SocketAddress $client, string $data): Promise
    {
        return call(function () use ($client, $data) {
            $this->configuration->getLogger()->incomingPacket($data, $client);

            $query = new Message($this->decoder->decode($data));

            $this->configuration->getLogger()->query($query);

            /** @var Message $answer */
            $answer = yield $this->configuration->getResolver()->query($query);

            $this->configuration->getLogger()->answerQuery($answer, $client);

            return $answer;
        });
    }
}
