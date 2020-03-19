<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Resolver;

use Amp\Promise;
use Amp\Socket\EncryptableSocket;
use LibDNS\Decoder\Decoder;
use LibDNS\Encoder\Encoder;
use LibDNS\Messages\Message;
use PeeHaa\AsyncDnsServer\Exception\NetworkError;
use PeeHaa\AsyncDnsServer\Logger\Logger;
use function Amp\call;
use function Amp\Socket\connect;

final class External implements Resolver
{
    private Logger $logger;

    private Encoder $encoder;

    private Decoder $decoder;

    private string $ipAddress;

    private int $port;

    public function __construct(Logger $logger, Encoder $encoder, Decoder $decoder, string $ipAddress, int $port = 53)
    {
        $this->logger    = $logger;
        $this->encoder   = $encoder;
        $this->decoder   = $decoder;
        $this->ipAddress = $ipAddress;
        $this->port      = $port;
    }

    /**
     * @return Promise<Message>
     */
    public function query(Message $message): Promise
    {
        return call(function () use ($message) {
            try {
                $this->logger->openConnectionToExternalResolver($this->ipAddress, $this->port);

                /** @var EncryptableSocket $socket */
                $socket = yield connect(sprintf('udp://%s:%d', $this->ipAddress, $this->port));

                $this->logger->connectedToExternalResolver($this->ipAddress, $this->port);

                $this->logger->sendQueryToExternalResolver($message, $this->ipAddress, $this->port);

                $packet = $this->encoder->encode($message);

                $this->logger->sendPacketToExternalResolver($packet, $this->ipAddress, $this->port);

                yield $socket->write($packet);
            } catch (\Throwable $e) {
                $this->logger->networkError($e);

                throw new NetworkError($e);
            }

            while (null !== $chunk = yield $socket->read()) {
                $this->logger->receivedResponseFromExternalResolver($chunk, $this->ipAddress, $this->port);

                $message = $this->decoder->decode($chunk);

                $this->logger->receivedMessageFromExternalResolver($message, $this->ipAddress, $this->port);

                return $message;
            }

            throw new NetworkError();
        });
    }
}
