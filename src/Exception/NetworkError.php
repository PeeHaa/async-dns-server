<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Exception;

final class NetworkError extends Exception
{
    public function __construct(\Throwable $previousException = null)
    {
        parent::__construct('Something went wrong', 0, $previousException);
    }
}
