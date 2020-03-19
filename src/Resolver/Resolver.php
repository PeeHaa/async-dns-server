<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Resolver;

use Amp\Promise;
use LibDNS\Messages\Message;

interface Resolver
{
    public function query(Message $message): Promise;
}
