<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer;

use LibDNS\Messages\Message as DnsMessage;
use LibDNS\Records\Question;
use LibDNS\Records\Record;
use LibDNS\Records\RecordCollection;
use LibDNS\Records\RecordCollectionFactory;
use LibDNS\Records\Resource;
use LibDNS\Records\Types\TypeFactory;
use PeeHaa\AsyncDnsServer\Exception\UnknownRecordType;

final class Message
{
    private DnsMessage $message;

    public function __construct(DnsMessage $message)
    {
        $this->message = $message;
    }

    public static function fromArray(array $data): self
    {
        $typeFactory = new TypeFactory();

        $message = new DnsMessage(new RecordCollectionFactory(), $data['type']);

        $message->setID($data['id']);
        $message->setOpCode($data['opCode']);
        $message->isAuthoritative($data['authoritative']);
        $message->isTruncated($data['truncated']);
        $message->isRecursionDesired($data['recursionDesired']);
        $message->isRecursionAvailable($data['recursionAvailable']);
        $message->setResponseCode($data['responseCode']);

        foreach (self::convertRecords($data['questionRecords'], $typeFactory) as $record) {
            $message->getQuestionRecords()->add($record);
        }

        foreach (self::convertRecords($data['answerRecords'], $typeFactory) as $record) {
            $message->getAnswerRecords()->add($record);
        }

        foreach (self::convertRecords($data['answerRecords'], $typeFactory) as $record) {
            $message->getAnswerRecords()->add($record);
        }

        foreach (self::convertRecords($data['authorityRecords'], $typeFactory) as $record) {
            $message->getAuthorityRecords()->add($record);
        }

        foreach (self::convertRecords($data['additionalRecords'], $typeFactory) as $record) {
            $message->getAdditionalRecords()->add($record);
        }

        return new self($message);
    }

    /**
     * @param array $collectionData
     * @return array<Record>
     * @throws \Exception
     */
    private static function convertRecords(array $collectionData, TypeFactory $typeFactory): array
    {
        $records = [];

        foreach ($collectionData as $recordData) {
            if (!in_array($recordData['recordType'], [Question::class, Resource::class])) {
                throw new \Exception('unknown type');
            }

            if ($recordData['recordType'] === Question::class) {
                $record = new Question($typeFactory, $recordData['type']);
            }

            if ($recordData['recordType'] === Resource::class) {
                $record = new Resource($typeFactory, $recordData['type'], unserialize($recordData['data']));

                $record->setTTL($recordData['ttl']);
            }

            $record->setName($recordData['name']);
            $record->setClass($recordData['class']);

            $records[] = $record;
        }

        return $records;
    }

    public function getId(): int
    {
        return $this->message->getID();
    }

    public function getType(): int
    {
        return $this->message->getType();
    }

    public function getOpCode(): int
    {
        return $this->message->getOpCode();
    }

    public function isAuthoritative(): bool
    {
        return $this->message->isAuthoritative();
    }

    public function isTruncated(): bool
    {
        return $this->message->isTruncated();
    }

    public function isRecursionDesired(): bool
    {
        return $this->message->isRecursionDesired();
    }

    public function isRecursionAvailable(): bool
    {
        return $this->message->isRecursionAvailable();
    }

    public function getResponseCode(): int
    {
        return $this->message->getResponseCode();
    }

    public function getQuestionRecords(): RecordCollection
    {
        return $this->message->getQuestionRecords();
    }

    public function getAnswerRecords(): RecordCollection
    {
        return $this->message->getAnswerRecords();
    }

    public function getAuthorityRecords(): RecordCollection
    {
        return $this->message->getAuthorityRecords();
    }

    public function getAdditionalRecords(): RecordCollection
    {
        return $this->message->getAdditionalRecords();
    }

    public function getMessage(): DnsMessage
    {
        return $this->message;
    }

    public function toArray(): array
    {
        return [
            'id'                 => $this->getId(),
            'type'               => $this->getType(),
            'opCode'             => $this->getOpCode(),
            'authoritative'      => $this->isAuthoritative(),
            'truncated'          => $this->isTruncated(),
            'recursionDesired'   => $this->isRecursionDesired(),
            'recursionAvailable' => $this->isRecursionAvailable(),
            'responseCode'       => $this->getResponseCode(),
            'questionRecords'    => $this->convertRecordCollectionToArray($this->getQuestionRecords()),
            'answerRecords'      => $this->convertRecordCollectionToArray($this->getAnswerRecords()),
            'authorityRecords'   => $this->convertRecordCollectionToArray($this->getAuthorityRecords()),
            'additionalRecords'  => $this->convertRecordCollectionToArray($this->getAdditionalRecords()),
        ];
    }

    private function convertRecordCollectionToArray(RecordCollection $recordCollection): array
    {
        $records = [];

        foreach ($recordCollection as $record) {
            $records[] = $this->convertRecordToArray($record);
        }

        return $records;
    }

    private function convertRecordToArray(Record $record): array
    {
        $data = [
            'recordType' => get_class($record),
            'type'       => $record->getType(),
            'name'       => $record->getName(),
            'class'      => $record->getClass(),
        ];

        if ($record instanceof Question) {
            return $data;
        }

        if ($record instanceof Resource) {
            $data['data'] = serialize($record->getData());
            $data['ttl']  = $record->getTTL();

            return $data;
        }

        throw new UnknownRecordType(get_class($record));
    }
}
