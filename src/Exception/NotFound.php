<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Exception;

final class NotFound extends Exception
{
    /**
     * @todo: more useful messages based on records in query and resolver
     */
    public function __construct()
    {
        parent::__construct('Could not find record');
    }
}
