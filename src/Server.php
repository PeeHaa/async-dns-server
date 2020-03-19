<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer;

use Amp\Promise;
use Amp\Socket\DatagramSocket;
use Amp\Success;
use LibDNS\Decoder\Decoder;
use LibDNS\Encoder\Encoder;
use PeeHaa\AsyncDnsServer\Logger\Logger;
use PeeHaa\AsyncDnsServer\Resolver\Resolver;
use function Amp\asyncCall;

final class Server
{
    private Logger $logger;

    private Resolver $resolver;

    private Encoder $encoder;

    private Decoder $decoder;

    private string $ipAddress;

    private int $port;

    public function __construct(
        Logger $logger,
        Resolver $resolver,
        Encoder $encoder,
        Decoder $decoder,
        string $ipAddress,
        int $port = 53
    ) {
        $this->logger    = $logger;
        $this->resolver  = $resolver;
        $this->encoder   = $encoder;
        $this->decoder   = $decoder;
        $this->ipAddress = $ipAddress;
        $this->port      = $port;
    }

    /**
     * @return Promise<null>
     */
    public function start(): Promise
    {
        asyncCall(function () {
            $server = DatagramSocket::bind(sprintf('%s:%d', $this->ipAddress, $this->port));

            $this->logger->started($this->ipAddress, $this->port);

            while ([$client, $data] = yield $server->receive()) {
                $this->logger->incomingPacket($data, $client);

                $query = $this->decoder->decode($data);

                $this->logger->query($query);

                $answer = yield $this->resolver->query($query);

                $this->logger->answerQuery($answer, $client);

                var_dump($client);

                yield $server->send($client, $this->encoder->encode($answer));
            }
        });

        return new Success();
    }
}
