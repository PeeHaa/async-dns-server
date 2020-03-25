<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Resolver;

use Amp\Promise;
use Amp\Socket\EncryptableSocket;
use LibDNS\Decoder\Decoder;
use LibDNS\Encoder\Encoder;
use LibDNS\Records\RecordCollection;
use LibDNS\Records\Resource;
use LibDNS\Records\ResourceTypes;
use PeeHaa\AsyncDnsServer\Logger\Logger;
use PeeHaa\AsyncDnsServer\Message;
use function Amp\call;
use function Amp\Socket\connect;

final class Recursive implements Resolver
{
    private Logger $logger;

    private Encoder $encoder;

    private Decoder $decoder;

    private int $numberOfRecursionAllowed;

    public function __construct(Logger $logger, Encoder $encoder, Decoder $decoder, int $maximumRecursionDepth = 12)
    {
        $this->logger                   = $logger;
        $this->encoder                  = $encoder;
        $this->decoder                  = $decoder;
        $this->numberOfRecursionAllowed = $maximumRecursionDepth;
    }

    /**
     * @inheritDoc
     */
    public function query(Message $message): Promise
    {
        return call(function () use ($message) {
            /** @var Message $answer */
            // @todo: pick a random root server and resolve it using the root hints resolve
            $answer = yield $this->runQuery(sprintf('udp://%s:%d', '192.33.4.12', 53), $message);

            $this->numberOfRecursionAllowed--;

            while ($this->needsFurtherRecursion($answer)) {
                if (!$this->numberOfRecursionAllowed) {
                    $answer->getMessage()->setResponseCode(5);

                    return $answer;
                }

                $answer = yield $this->getNextAnswerInChain($message, $answer);

                $this->numberOfRecursionAllowed--;
            }

            return $answer;
        });
    }

    /**
     * @return Promise<Message>
     */
    private function runQuery(string $serverAddress, Message $message): Promise
    {
        return call(function () use ($serverAddress, $message) {
            /** @var EncryptableSocket $socket */
            $socket = yield connect($serverAddress);
            $packet = $this->encoder->encode($message->getMessage());

            yield $socket->write($packet);

            $answer = $this->decoder->decode(yield $socket->read());

            $socket->close();

            return new Message($answer);
        });
    }

    private function needsFurtherRecursion(Message $message): bool
    {
        if ($message->getResponseCode() !== 0) {
            return false;
        }

        if ($message->isAuthoritative()) {
            $message->getMessage()->isAuthoritative(false);

            return false;
        }

        if (!$message->getAuthorityRecords()->count()) {
            return false;
        }

        return true;
    }

    /**
     * @return Promise<Message>
     */
    private function getNextAnswerInChain(Message $question, Message $previousAnswer): Promise
    {
        return call(function () use ($question, $previousAnswer) {
            $additionalRecords = $this->parseAdditionalRecords($previousAnswer->getAdditionalRecords());

            /** @var Resource $authorityRecord */
            foreach ($previousAnswer->getAuthorityRecords() as $authorityRecord) {
                // @todo: support ipv6 (AAAA) when enabled
                if (!isset($additionalRecords[ResourceTypes::A][$authorityRecord->getData()->getField(0)->getValue()])) {
                    // @todo: we should do a lookup instead here
                    continue;
                }

                $address = sprintf('udp://%s:53', $additionalRecords[ResourceTypes::A][$authorityRecord->getData()->getField(0)->getValue()]);

                /** @var Message $answer */
                $answer = yield $this->runQuery($address, $question);

                if (in_array($answer->getResponseCode(), [2, 5], true)) {
                    continue;
                }

                return $answer;
            }
        });
    }

    /**
     * @todo: do we want to cache these records or consider them non authoritative?
     *
     * @return array<int,array<string,string>>
     */
    private function parseAdditionalRecords(RecordCollection $recordCollection): array
    {
        $additionalRecords = [];

        foreach ($recordCollection as $record) {
            if (!isset($additionalRecords[$record->getType()])) {
                $additionalRecords[$record->getType()] = [];
            }

            $additionalRecords[$record->getType()][$record->getName()->getValue()] = $record->getData()->getField(0)->getValue();
        }

        return $additionalRecords;
    }
}
