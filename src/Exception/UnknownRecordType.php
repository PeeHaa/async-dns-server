<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Exception;

final class UnknownRecordType extends Exception
{
    public function __construct(string $type)
    {
        parent::__construct(
            sprintf('Cannot convert record of type %s to array', $type),
        );
    }
}
