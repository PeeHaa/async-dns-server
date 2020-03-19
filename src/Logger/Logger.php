<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Logger;

use Amp\Socket\SocketAddress;
use LibDNS\Messages\Message;
use LibDNS\Records\Record;
use LibDNS\Records\RecordCollection;
use Monolog\Logger as MonologLogger;

final class Logger
{
    private MonologLogger $logger;

    public function __construct(MonologLogger $logger)
    {
        $this->logger = $logger;
    }

    public function started(string $ipAddress, int $port): void
    {
        $this->logger->info(sprintf('DNS server started at %s:%d', $ipAddress, $port));
    }

    public function incomingPacket(string $packet, SocketAddress $client): void
    {
        $this->logger->debug(sprintf('Received package from %s: %s', $client->toString(), bin2hex($packet)));
    }

    public function query(Message $message): void
    {
        $this->logger->debug('Received query', ['message' => $this->convertMessageToArray($message)]);
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
            ['message' => $this->convertMessageToArray($message)],
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
            ['message' => $this->convertMessageToArray($message)],
        );
    }

    public function answerQuery(Message $message, SocketAddress $client): void
    {
        $this->logger->debug(
            sprintf('Sending answer to %s', $client->toString()),
            ['message' => $this->convertMessageToArray($message)],
        );
    }

    public function networkError(\Throwable $e): void
    {
        $this->logger->error($e->getMessage(), ['exception' => $e]);
    }

    private function convertMessageToArray(Message $message): array
    {
        return [
            'id'                 => $message->getID(),
            'type'               => $message->getType(),
            'opCode'             => $message->getOpCode(),
            'authoritative'      => $message->isAuthoritative(),
            'truncated'          => $message->isTruncated(),
            'recursionDesired'   => $message->isRecursionDesired(),
            'recursionAvailable' => $message->isRecursionAvailable(),
            'responseCode'       => $message->getResponseCode(),
            'questions'          => $this->convertRecordCollectionToArray($message->getQuestionRecords()),
            'answers'            => $this->convertRecordCollectionToArray($message->getAnswerRecords()),
            'authorityRecords'   => $this->convertRecordCollectionToArray($message->getAuthorityRecords()),
            'additionalRecords'  => $this->convertRecordCollectionToArray($message->getAdditionalRecords()),
        ];
    }

    private function convertRecordCollectionToArray(RecordCollection $recordCollection): array
    {
        $records = [];

        /** @var Record $record */
        foreach ($recordCollection as $record) {
            $records[] = [
                'name'  => $record->getName(),
                'type'  => $record->getType(),
                'class' => $record->getClass(),
            ];
        }

        return $records;
    }
}
