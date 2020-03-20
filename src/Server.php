<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer;

use Amp\Promise;
use Amp\Socket\DatagramSocket;
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
